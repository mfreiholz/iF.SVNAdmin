<?php

class ProviderService extends ServiceBase {

	public function processRequest(WebRequest $req, WebResponse $res) {
		$a = $req->getParameter("action");
		switch ($a) {
			case "list":
			default:
				return $this->processList($req, $res);
		}
		return parent::processRequest($request, $response);
	}

	private function processList(WebRequest $req, WebResponse $res) {
		$type = $req->getParameter("type");
		if (empty($type))
			return $this->processErrorMissingParameters($req, $res);

		$js = array();
		foreach ($this->getAppEngine()->getKnownProviders($type) as &$prov) {
			$js[] = JsonSerializer::fromProvider($prov);
		}
		$res->done2json($js);
		return true;
	}

}