<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Presentation;

use MicroHis\NotasSoap\Application\AssociateDiagnosis;
use MicroHis\NotasSoap\Application\CorrectSoapNote;
use MicroHis\NotasSoap\Application\GetSoapNote;
use MicroHis\NotasSoap\Application\RegisterSoapNote;
use MicroHis\NotasSoap\Application\SignSoapNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Domain\SoapSections;

/** Controlador MVC: lee la petición, invoca el caso de uso (modelo) y entrega el resultado a la vista. Sin SQL ni reglas de negocio. */
final class SoapNoteController
{
    public function __construct(
        private readonly RegisterSoapNote $register,
        private readonly AssociateDiagnosis $associate,
        private readonly GetSoapNote $show,
        private readonly SignSoapNote $signer,
        private readonly CorrectSoapNote $corrector,
        private readonly JsonView $view,
    ) {
    }

    public function show(Request $request, string $noteId): array
    {
        return $this->respond(200, function () use ($request, $noteId): array {
            $note = $this->show->execute($request->actor(), $noteId);
            $s = $note->sections;
            return [
                'clinical_note_id' => $note->id, 'care_episode_id' => $note->episodeId, 'author_user_ref_id' => $note->authorId, 'status' => $note->status, 'version_no' => $note->versionNo, 'supersedes_id' => $note->supersedesId,
                'sections' => ['subjective' => $s?->subjective, 'objective' => $s?->objective, 'assessment' => $s?->assessment, 'plan' => $s?->plan],
                'diagnoses' => array_map(static fn ($d): array => ['code' => $d->code, 'type' => $d->type], $note->diagnoses),
            ];
        });
    }

    public function registerNote(Request $request, string $episodeId): array
    {
        return $this->respond(201, function () use ($request, $episodeId): array {
            $actor = $request->actor();
            $sections = new SoapSections(...array_map($request->text(...), ['subjective', 'objective', 'assessment', 'plan']));
            return ['clinical_note_id' => $this->register->execute($actor, $episodeId, $sections), 'status' => 'draft'];
        });
    }

    public function associateDiagnosis(Request $request, string $noteId): array
    {
        return $this->respond(201, function () use ($request, $noteId): array {
            $actor = $request->actor();
            $diagnosis = new Diagnosis($request->text('code'), $request->text('type'));
            $this->associate->execute($actor, $noteId, $diagnosis);
            return ['clinical_note_id' => $noteId, 'code' => $diagnosis->code, 'type' => $diagnosis->type];
        });
    }

    public function correct(Request $request, string $noteId): array
    {
        return $this->respond(201, function () use ($request, $noteId): array {
            $actor = $request->actor();
            $sections = new SoapSections(...array_map($request->text(...), ['subjective', 'objective', 'assessment', 'plan']));
            $id = $this->corrector->execute($actor, $noteId, $sections, $request->text('correction_reason'));
            return ['clinical_note_id' => $id, 'supersedes_id' => $noteId, 'status' => 'draft'];
        });
    }

    public function sign(Request $request, string $noteId): array
    {
        return $this->respond(200, function () use ($request, $noteId): array {
            $this->signer->execute($request->actor(), $noteId);
            return ['clinical_note_id' => $noteId, 'status' => 'signed'];
        });
    }

    /** Traduce resultado o excepción a estado + JSON; cada error termina la petición aquí. */
    private function respond(int $status, callable $action): array
    {
        try {
            return $this->view->render($status, $action());
        } catch (SoapRuleViolation $e) {
            return $this->view->render($e->status, ['code' => $e->getMessage()]);
        } catch (\RuntimeException) {
            return $this->view->render(500, ['code' => 'SOAP_PERSISTENCE_ERROR']);
        }
    }
}
