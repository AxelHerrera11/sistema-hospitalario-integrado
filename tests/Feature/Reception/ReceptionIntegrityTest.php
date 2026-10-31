<?php

namespace Tests\Feature\Reception;

use App\Modules\Reception\Application\ReceiveSpecimen;
use App\Modules\Reception\Infrastructure\HospitalReceptionRepository;
use App\Modules\Reception\Infrastructure\OrderProjection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ReceptionIntegrityTest extends ReceptionTestCase
{
    private function receive(array $input): array
    {
        return (new ReceiveSpecimen(new HospitalReceptionRepository, new OrderProjection))
            ->execute($this->hospitalA, $this->actor, $input, new \DateTimeImmutable());
    }

    public function test_outbox_failure_rolls_back_specimen(): void
    {
        DB::connection('hospital')->statement('ALTER TABLE outbox_events ADD CONSTRAINT simulated_failure CHECK (false)');
        try { $this->receive($this->payload()); $this->fail('Se ignoró el fallo de outbox'); }
        catch (\PDOException) {
            $this->assertSame(0, DB::connection('hospital')->table('specimens')->count());
        }
    }

    public function test_refresh_does_not_reopen_accepted_order(): void
    {
        $this->receive($this->payload()); $this->projection();
        $this->expectException(\App\Modules\Reception\Domain\ReceptionError::class);
        $this->expectExceptionCode(409); $this->receive($this->payload());
    }

    public function test_projection_rejects_wrong_hospital(): void
    {
        $row = $this->projection(); $row['tenant_id'] = $this->hospitalB;
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new OrderProjection)->import($row);
    }

    public function test_competing_connection_cannot_receive_locked_order(): void
    {
        $config = config('database.connections.hospital');
        $other = new \PDO('pgsql:host='.$config['host'].';port='.$config['port'].';dbname='.$config['database'],
            $config['username'], $config['password']);
        $other->exec('SET search_path TO '.$this->schema.',public');
        $other->beginTransaction();
        $statement = $other->prepare('SELECT * FROM reception_order_items WHERE item_id = ? FOR UPDATE');
        $statement->execute([$this->item]);
        DB::connection('hospital')->statement("SET lock_timeout = '100ms'");
        try { $this->receive($this->payload()); $this->fail('Se ignoró el bloqueo'); }
        catch (\Illuminate\Database\QueryException $error) { $this->assertSame('55P03', (string) $error->getCode()); }
        finally { $other->rollBack(); }
        $this->assertSame('accepted', $this->receive($this->payload())['status']);
    }
}
