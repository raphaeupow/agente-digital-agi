<?php

use robot\tools\Debug;

class Main
{
	public function getDadosCliente($parameters)
	{
		$pdo = new PDO("mysql:host=".getenv("DB_HOST").";dbname=".getenv("DB_NAME"), getenv("DB_USER"), getenv("DB_PASS"));

		$stmt = $pdo->prepare("select  m.nome, m.dados from calls_full c join mailing m on m.id =c.mailing_id where c.id = :callId limit 1");
		$stmt->execute([":callId" => $parameters["callId"]]);
		$data = $stmt->fetch(PDO::FETCH_ASSOC);

		if (empty($data)) {
			throw new \Exception("Nenhum dado encontrado para o callId: ".$parameters["callId"]);
		}

		$data["dados"] = json_decode($data["dados"], 1);
		
		
		return [
			"nome" => $data["nome"],
			"valor" => $data["dados"]["x_var3"]
		];
	}
}