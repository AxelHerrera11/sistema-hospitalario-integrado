<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Application;

use MicroHis\Medicos\Domain\DoctorRuleViolation;
use MicroHis\Medicos\Domain\Limits;
use MicroHis\Medicos\Domain\Period;
use MicroHis\Medicos\Domain\SlotPlanner;

/** UC-MED-05 y 06: franjas libres por especialidad en una fecha (RF-MED-06, RF-MED-07). Solo lectura. */
final class QueryAvailability
{
    public function __construct(
        private readonly SpecialtyCatalog $catalog,
        private readonly DoctorsBySpecialty $doctors,
        private readonly DoctorScheduleProvider $schedule,
        private readonly AppointmentReader $appointments,
        private readonly SlotPlanner $planner,
    ) {
    }

    /** @return array{code: string, doctors: list<array{doctor_id: string, status: string, slots: list<string>}>} */
    public function execute(string $specialtyCode, string $date): array
    {
        Period::isDate($date) || throw new DoctorRuleViolation('INVALID_DATE_RANGE', 422);
        Limits::within($specialtyCode, Limits::SPECIALTY_CODE);
        trim($specialtyCode) !== '' || throw new DoctorRuleViolation('PAYLOAD_INVALID', 422); // D-04: sin especialidad no es una inactiva
        $specialtyId = $this->catalog->activeSpecialtyId($specialtyCode) ?? throw new DoctorRuleViolation('SPECIALTY_INACTIVE', 422);
        $result = [];
        foreach ($this->doctors->doctorsWith($specialtyId, $date) as $id) {
            $windows = $this->schedule->scheduleFor($id, $date);
            $result[] = $windows === null
                ? ['doctor_id' => $id, 'status' => 'SCHEDULE_NOT_CONFIGURED', 'slots' => []]
                : ['doctor_id' => $id, 'status' => 'OK', 'slots' => $this->planner->freeSlots($windows, $this->appointments->busyIntervals($id, $date))];
        }
        return ['code' => $result === [] ? 'NO_DOCTORS_FOR_SPECIALTY' : 'OK', 'doctors' => $result];
    }
}
