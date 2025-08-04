<?php
namespace robot\tracer;

use robot\Tools\Database;
use robot\Tools\Debug;


class Attendance
{
    public static function create($scriptId, $phone, $uniqueId, $keyId, $isTest)
    {
        try {
            $sql = "INSERT INTO attendances(start, script_id, phone, unique_id, key_id, is_test) VALUES (now(),?,?,?,?,?)";
            $stmt = Database::get()->prepare($sql);
            $stmt->execute([$scriptId, $phone, $uniqueId, (int)$keyId, $isTest?"1":"0"]);
            return Database::get()->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception("Erro to create attendence: ".$e->getMessage());
        }
    }

    public static function finish($id, $hangoutDirection, $statusId, $variables)
    {
        try {
            $sql = "UPDATE attendances SET end = now(), timer = TIMESTAMPDIFF(SECOND, start, NOW()), hangout_direction = ?, status_id = ?, variables = ? WHERE id = ?";
            $stmt = Database::get()->prepare($sql);
            $stmt->execute([$hangoutDirection, $statusId, $variables, $id]);
        } catch (\PDOException $e) {
            throw new \Exception("Erro ao finalizar o atendimento: " . $e->getMessage());
        }
    }
}