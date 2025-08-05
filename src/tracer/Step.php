<?php

namespace robot\tracer;

use robot\Tools\Database;
use robot\Tools\Debug;
use robot\Variable;

class Step
{
    private static $id;
    private static $scriptId;

    public static function create($attendanceId, $componentName, $componentId, $scriptId) 
    {   
        Variable::systemSet("debug", []);
        self::$scriptId = $scriptId;    
        try {
            $sql = "INSERT INTO steps (
                        attendance_id,
                        component_name,
                        component_id,
                        script_id,
                        start
                    ) VALUES (:attendanceId, :componentName, :componentId, :scriptId, :start)";

            $stmt = Database::get()->prepare($sql);
            $stmt->execute([
                'attendanceId' => $attendanceId,
                'componentName' => $componentName,
                'componentId' => $componentId,
                'scriptId' => $scriptId,
                'start' => date('Y-m-d H:i:s')
            ]);
            
        

            self::$id = Database::get()->lastInsertId();
        } catch (\PDOException $e) {
            throw new \Exception("Erro to create step: " . $e->getMessage());
        }
    }

    public static function finish() {
        try {
            $sql = "UPDATE steps 
            SET 
                end = NOW(), 
                timer = TIMESTAMPDIFF(SECOND, start, NOW()) 
            WHERE id = :id";
            $stmt = Database::get()->prepare($sql);
            $stmt->execute([
                'id' => self::$id
            ]);
            $stmt = Database::get()->prepare($sql);
            $stmt->execute(['id' => self::$id]);


            $debug = Variable::systemGet("debug");
            if (is_array($debug)) {
                $sql = "INSERT INTO debug (step_id, type, datetime, message, script_id) VALUES (:step_id, :type, :datetime, :message, :script_id)";
                $stmt = Database::get()->prepare($sql); // Prepara só uma vez
                foreach ($debug as $log) {
                    $stmt->execute([
                        'step_id' => self::$id,
                        'script_id' => self::$scriptId,   
                        'type' => $log['type'],
                        'datetime' => $log["datetime"],
                        'message' => $log["message"]
                    ]);
                }
            }



            $debug[] = ["type" => "info", "datetime" => date('d-m-Y H:i:s'), "message" => "Step finished"];
            Variable::systemSet("debug", $debug);
        } catch (\PDOException $e) {
            throw new \Exception("Erro to update step: " . $e->getMessage());
        }
    }
}
