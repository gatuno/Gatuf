<?php
class Gatuf_Despachador {
	
	public static function despachar ($query = '') {
		try {
			$query = preg_replace('#^(/)+#', '/', '/'.$query);
			$req = new Gatuf_HTTP_Request($query);
			/* Justo aquí cargar las middleware_clases */
			$middleware = array();
			foreach (Gatuf::config('middleware_classes', array()) as $mw) {
				$middleware[] = new $mw();
			}
			$skip = false;
			foreach ($middleware as $mw) {
				if (method_exists($mw, 'process_request')) {
					$response = $mw->process_request($req);
					if ($response !== false) {
						// $response is a response
						if (Gatuf::config('pluf_runtime_header', false)) {
							$response->headers['X-Perf-Runtime'] = sprintf('%.5f', (microtime(true) - $GLOBALS['_PX_starttime']));
						}
						$response->render($req->method != 'HEAD' and !defined('IN_UNIT_TESTS'));
						$skip = true;
						break;
					}
				}
			}
			if ($skip === false) {
				$response = self::match($req);
				if (!empty($req->response_vary_on)) {
					$response->headers['Vary'] = $req->response_vary_on;
				}
				/* Procesar la respuesta con las middleware_clases */
				$middleware = array_reverse($middleware);
				foreach ($middleware as $mw) {
					if (method_exists($mw, 'process_response')) {
						$response = $mw->process_response($req, $response);
					}
				}
				/* No sé para qué es esto ... */
				if (Gatuf::config('pluf_runtime_header', false)) {
					$response->headers['X-Perf-Runtime'] = sprintf('%.5f', (microtime(true) - $GLOBALS['_PX_starttime']));
				}
				$response->render($req->method != 'HEAD');
			}
		} catch (Exception $e) {
			if (Gatuf::config('debug', false) == true) {
				$response = new Gatuf_HTTP_Response_ServerErrorDebug($e);
			} else {
				$response = new Gatuf_HTTP_Response_ServerError($e);
			}
			$response->render($req->method != 'HEAD');
		}
	}
	
	public static function match ($req, $firstpass = true) {
		try {
			$views = $GLOBALS['_GATUF_vistas'];
			$to_match = $req->query;
			$n = count ($views);
			$i = 0;
			while ($i < $n) {
				$ctl = $views [$i];
				if (preg_match ($ctl['regex'], $to_match, $match)) {
					if (!isset ($ctl['sub'])) {
						return self::send ($req, $ctl, $match);
					} else {
						$views = $ctl['sub'];
						$i = 0;
						$n = count ($views);
						$to_match = substr ($to_match, strlen ($match[0]));
						continue;
					}
				}
				$i++;
			}
		} catch (Gatuf_HTTP_Error404 $e) {
			
		}
		
		if ($firstpass and substr ($req->query, -1) != '/') {
			$req->query .= '/';
			$res = self::match ($req, false);

			if ($res->status_code != 404) {
				Gatuf::loadFunction('Gatuf_HTTP_URL_urlForView');
				$name = (isset($req->view[0]['name'])) ?
					$req->view[0]['name'] :
					$req->view[0]['model'].'::'.$req->view[0]['method'];
				$url = Gatuf_HTTP_URL_urlForView($name, array_slice($req->view[1], 1));
				return new Gatuf_HTTP_Response_Redirect($url, 301);
			}
		}
        return new Gatuf_HTTP_Response_NotFound($req);
	}
	
	public static function send($req, $ctl, $match) {
		/* Guardar la vista y el match en la petición http */
		$req->view = array ($ctl, $match);
		
		/* Cargar la clase vista controladora */
		$m = new $ctl['model']();
		/* Aquí verificar por precondiciones antes de la llamada */
		if (isset($m->{$ctl['method'].'_precond'})) {
			$preconds = $m->{$ctl['method'].'_precond'};
            if (!is_array($preconds)) {
                $preconds = array($preconds);
            }
            foreach ($preconds as $precond) {
                if (!is_array($precond)) {
                    $res = call_user_func_array(
                                                explode('::', $precond), 
                                                array(&$req)
                                                );
                } else {
                    $res = call_user_func_array(
                                                explode('::', $precond[0]), 
                                                array_merge(array(&$req), 
                                                            array_slice($precond, 1))
                                                );
                }
                if ($res !== true) {
                    return $res;
                }
            } 
        }
		
		if (!isset ($ctl['params'])) {
			return $m->$ctl['method']($req, $match);
		} else {
			return $m->$ctl['method']($req, $match, $ctl['params']);
		}
	}
	
	public static function loadControllers($file) {
		if (file_exists($file)) {
			$GLOBALS['_GATUF_vistas'] = include $file;
			return true;
		}
		return false;
	}
}
