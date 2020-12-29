<?php

class Gatuf_ODS {
	public $nombre;
	public $meta;
	public $hojas;
	private $max_col;
	private $max_row;
	private $auto_sytles;
	private $merged_cells;
	
	public function __construct() {
		/* Inicializar algunos atributos meta */
		$this->meta = array(
			array(
				'xmltag' => 'meta:generator',
				'data' => 'Gatuf_ODF/0.1',
			),
			array(
				'xmltag' => 'meta:initial-creator',
				'data' => 'Sistema de calificaciones/Gatuf',
			),
			array(
				'xmltag' => 'dc:creator',
				'data' => 'Sistema de Calificaciones/Gatuf',
			),
			array(
				'xmltag' => 'meta:creation-date',
				'data' => strftime('%Y-%m-%dT%H:%M:%S'),
			),
			array(
				'xmltag' => 'dc:date',
				'data' => strftime('%Y-%m-%dT%H:%M:%S'),
			),
			array(
				'xmltag' => 'meta:editing-cycles',
				'data' => 1,
			),
		);
		$this->max_col = array();
		$this->max_row = array();
		$this->merged_cells = array();
		
		$this->auto_styles = array(
			array(
				'xmltag' => 'number:text-style',
				'attrs' => array(
					'style:name' => 'CELTEXT',
				),
				'subtags' => array(
					array(
						'xmltag' => 'number:text-content',
					),
				),
			),
			array(
				'xmltag' => 'style:style',
				'attrs' => array(
					'style:name' => 'cel',
					'style:family' => 'table-cell',
					'style:data-style-name' => 'CELTEXT',
				),
			),
			array(
				'xmltag' => 'style:style',
				'attrs' => array(
					'style:name' => 'merged-centrado',
					'style:family' => 'table-cell',
				),
				'subtags' => array(
					array(
						'xmltag' => 'style:table-cell-properties',
						'attrs' => array(
							'style:text-align-source' => 'fix',
							'style:repeat-content' => 'false',
							'style:vertical-align' => 'middle',
						)
					),
					array(
						'xmltag' => 'style:paragraph-properties',
						'attrs' => array(
							'fo:text-align' => 'center',
						),
					),
				),
			),
		);
	}
	
	private function createTag($dom, $tag) {
		$tagname = $tag['xmltag'];
		
		$tag_nueva = $dom->createElement($tagname);
		
		if (isset($tag['attrs'])) {
			foreach ($tag['attrs'] as $key => $value) {
				$tag_nueva->setAttribute($key, $value);
			}
		}
		
		if (isset($tag['subtags'])) {
			foreach ($tag['subtags'] as $value) {
				$subtag = $this->createTag($dom, $value);
				$tag_nueva->appendChild($subtag);
			}
		}
		if (isset($tag['data'])) {
			$tag_nueva->nodeValue = $tag['data'];
		}
		return $tag_nueva;
	}
	
	public function addNewSheet($sheet_name) {
		if (!isset($this->hojas[$sheet_name])) {
			$this->hojas[$sheet_name] = array();
		}
		$this->max_col[$sheet_name] = 1;
		$this->max_row[$sheet_name] = 1;
		$this->merged_cells[$sheet_name] = array();
	}
	
	public function addStringCell($sheet, $row, $col, $string) {
		if ($row < 1 || $col < 1) {
			throw new Exception('Fila y columna inválidas');
		}
		if ($this->max_col[$sheet] < $col) {
			$this->max_col[$sheet] = $col;
		}
		if ($this->max_row[$sheet] < $row) {
			$this->max_row[$sheet] = $row;
		}
		
		if (!isset($this->hojas[$sheet][$row])) {
			$this->hojas[$sheet][$row] = array();
		}
		
		$this->hojas[$sheet][$row][$col] = array('office:value-type' => 'string', 'table:style-name' => 'cel', 'value' => $string);
	}
	
	public function addMergedStringCell($sheet, $row, $col, $string, $span_cols, $span_rows) {
		$this->addStringCell($sheet, $row, $col, $string);
		/*$this->hojas[$sheet][$row][$col]['table:style-name'] = 'merged-centrado';*/
		
		if ($span_cols == 1 && $span_rows == 1) {
			return;
		}
		$span = $row.':'.$col;
		
		$this->merged_cells[$sheet][$span] = array('col' => $span_cols, 'row' => $span_rows);
		for ($g = $row; $g < $span_rows - 1; $g++) {
			for ($h = $col; $h < $span_cols - 1; $h++) {
				$this->hojas[$sheet][$row][$col] = array('covered' => true);
			}
		}
	}
	
	public function addPercentageCell($sheet, $row, $col, $percentage) {
		if ($row < 1 || $col < 1) {
			throw new Exception('Fila y columna inválidas');
		}
		if ($this->max_col[$sheet] < $col) {
			$this->max_col[$sheet] = $col;
		}
		if ($this->max_row[$sheet] < $row) {
			$this->max_row[$sheet] = $row;
		}
		
		if (!isset($this->hojas[$sheet][$row])) {
			$this->hojas[$sheet][$row] = array();
		}
		
		$this->hojas[$sheet][$row][$col] = array('office:value-type' => 'float', 'office:value' => ($percentage / 100));
	}
	
	public function meta_set_title($title) {
		$this->meta['dc:title'] = $title;
	}
	
	public function meta_set_description($desc) {
		$this->meta['dc:description'] = $desc;
	}
	
	public function meta_set_subject($subject) {
		$this->meta['dc:subject'] = $subject;
	}
	
	public function construir_manifest() {
		$dom_manifest = new DOMDocument('1.0', 'UTF-8');
		
		$dom_manifest->formatOutput = true;
		
		$raiz = $dom_manifest->createElement('manifest:manifest');
		$dom_manifest->appendChild($raiz);
		
		/* Espacio de nombres */
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:manifest', 'urn:oasis:names:tc:opendocument:xmlns:manifest:1.0');
		
		$file_tag = $dom_manifest->createElement('manifest:file-entry');
		$file_tag->setAttribute('manifest:media-type', 'application/vnd.oasis.opendocument.spreadsheet');
		$file_tag->setAttribute('manifest:version', '1.2');
		$file_tag->setAttribute('manifest:full-path', '/');
		$raiz->appendChild($file_tag);
		
		/* El archivo content.xml */
		$file_tag = $dom_manifest->createElement('manifest:file-entry');
		$file_tag->setAttribute('manifest:media-type', 'text/xml');
		$file_tag->setAttribute('manifest:full-path', 'content.xml');
		$raiz->appendChild($file_tag);
		
		/* El archivo meta.xml */
		$file_tag = $dom_manifest->createElement('manifest:file-entry');
		$file_tag->setAttribute('manifest:media-type', 'text/xml');
		$file_tag->setAttribute('manifest:full-path', 'meta.xml');
		$raiz->appendChild($file_tag);
		
		/* El archivo styles.xml
		$file_tag = $dom_manifest->createElement ('manifest:file-entry');
		$file_tag->setAttribute ('manifest:media-type', 'text/xml');
		$file_tag->setAttribute ('manifest:full-path', 'styles.xml');
		$raiz->appendChild ($file_tag);
		*/
		/* El archivo settings.xml
		$file_tag = $dom_manifest->createElement ('manifest:file-entry');
		$file_tag->setAttribute ('manifest:media-type', 'text/xml');
		$file_tag->setAttribute ('manifest:full-path', 'settings.xml');
		$raiz->appendChild ($file_tag);
		*/
		
		return $dom_manifest;
	}
	
	public function construir_contenido() {
		$dom_contenido = new DOMDocument('1.0', 'UTF-8');
		
		$dom_contenido->formatOutput = true;
		
		$raiz = $dom_contenido->createElement('office:document-content');
		$dom_contenido->appendChild($raiz);
		
		/* Espacio de nombres XML */
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:style', 'urn:oasis:names:tc:opendocument:xmlns:style:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:table', 'urn:oasis:names:tc:opendocument:xmlns:table:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:draw', 'urn:oasis:names:tc:opendocument:xmlns:drawing:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:fo', 'urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:meta', 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:number', 'urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:presentation', 'urn:oasis:names:tc:opendocument:xmlns:presentation:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:svg', 'urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:chart', 'urn:oasis:names:tc:opendocument:xmlns:chart:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dr3d', 'urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:math', 'http://www.w3.org/1998/Math/MathML');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:form', 'urn:oasis:names:tc:opendocument:xmlns:form:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:script', 'urn:oasis:names:tc:opendocument:xmlns:script:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ooo', 'http://openoffice.org/2004/office');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ooow', 'http://openoffice.org/2004/writer');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:oooc', 'http://openoffice.org/2004/calc');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dom', 'http://www.w3.org/2001/xml-events');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xforms', 'http://www.w3.org/2002/xforms');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:rpt', 'http://openoffice.org/2005/report');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:of', 'urn:oasis:names:tc:opendocument:xmlns:of:1.2');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:grddl', 'http://www.w3.org/2003/g/data-view#');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:tableooo', 'http://openoffice.org/2009/table');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:field', 'urn:openoffice:names:experimental:ooo-ms-interop:xmlns:field:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:formx', 'urn:openoffice:names:experimental:ooxml-odf-interop:xmlns:form:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:css3t', 'http://www.w3.org/TR/css3-text/');
		
		$raiz->setAttribute('grddl:transformation', 'http://docs.oasis-open.org/office/1.2/xslt/odf2rdf.xsl');
		$raiz->setAttribute('office:version', '1.2');
		
		$tag_vacia = $dom_contenido->createElement('office:scripts');
		$raiz->appendChild($tag_vacia);
		
		/* Declaración de fuentes */
		$etiqueta_fuentes = $dom_contenido->createElement('office:font-face-decls');
		$raiz->appendChild($etiqueta_fuentes);
		
		/* Estilos automáticos */
		$etiqueta_estilos_automaticos = $this->createTag($dom_contenido, array('xmltag' => 'office:automatic-styles', 'subtags' => $this->auto_styles));
		$raiz->appendChild($etiqueta_estilos_automaticos);
		
		$tag_vacia = $dom_contenido->createElement('office:body');
		$raiz->appendChild($tag_vacia);
		
		$spreadsheet = $dom_contenido->createElement('office:spreadsheet');
		$tag_vacia->appendChild($spreadsheet);
		
		if (count($this->hojas) == 0) {
			throw new Exception('El libro no tiene hojas');
		}
		
		/* Recorrer todas las hojas */
		foreach ($this->hojas as $nombre_hoja => $datos_hoja) {
			$hoja = $dom_contenido->createElement('table:table');
			$hoja->setAttribute('table:name', $nombre_hoja);
			$spreadsheet->appendChild($hoja);
			
			$repeat_row = 1;
			ksort($datos_hoja);
			foreach ($datos_hoja as $numero_fila => $datos_fila) {
				/* Si se brincó alguna fila, escribirla vacia */
				if ($numero_fila - $repeat_row != 0) {
					$row = $dom_contenido->createElement('table:table-row');
					$hoja->appendChild($row);
					if ($numero_fila - $repeat_row != 1) { /* Cuando se brinque más de una */
						$row->setAttribute('table:number-rows-repeated', $numero_fila - $repeat_row);
					}
				}
				
				/* Crear la fila correspondiente */
				$row = $dom_contenido->createElement('table:table-row');
				$hoja->appendChild($row);
				
				$repeat_col = 1;
				
				/* Recorrer todas las celdas */
				ksort($datos_fila);
				foreach ($datos_fila as $numero_columna => $datos_celda) {
					/* Si se brincó alguna celda, escribirla vacia */
					if ($numero_columna - $repeat_col != 0) {
						$celda = $dom_contenido->createElement('table:table-cell');
						$row->appendChild($celda);
						if ($numero_columna - $repeat_col != 1) { /* Cuando se brinque más de una */
							$celda->setAttribute('table:number-columns-repeated', $numero_columna - $repeat_col);
						}
					}
					if (isset($datos_celda['covered'])) {
						$celda = $dom_contenido->createElement('table:table-cell');
						$row->appendChild($celda);
					} else {
						/* Crear la celda correspondiente */
						$celda = $dom_contenido->createElement('table:table-cell');
						$row->appendChild($celda);
					
						if (isset($datos_celda['value'])) {
							$value = $datos_celda['value'];
							$text_p = $dom_contenido->createElement('text:p', $value);
							$celda->appendChild($text_p);
							unset($datos_celda['value']);
						}
					
						foreach ($datos_celda as $attr => $attr_value) {
							$celda->setAttribute($attr, $attr_value);
						}
					
						$span = $numero_fila.':'.$numero_columna;
						if (isset($this->merged_cells[$nombre_hoja][$span])) {
							$celda->setAttribute('table:number-columns-spanned', $this->merged_cells[$nombre_hoja][$span]['col']);
							$celda->setAttribute('table:number-rows-spanned', $this->merged_cells[$nombre_hoja][$span]['row']);
						}
					}
					$repeat_col = $numero_columna + 1;
				} /* Fin FOR de celdas */
				
				$repeat_col--;
				
				/* Si después de recorrer todas las celdas, no es el máx de columnas usadas,
				 * rellenar con celdas vacias */
				if ($this->max_col[$nombre_hoja] - $repeat_col != 0) {
					$celda = $dom_contenido->createElement('table:table-cell');
					$row->appendChild($celda);
					
					if ($this->max_col[$nombre_hoja] - $repeat_col != 1) {
						$celda->setAttribute('table:number-columns-repeated', $this->max_col[$nombre_hoja] - $repeat_col);
					}
				}
				
				$repeat_row = $numero_fila + 1;
			} /* Fin FOR de filas */
		} /* Fin FOR de las hojas */
		
		return $dom_contenido;
	}
	
	public function construir_meta() {
		$dom_meta = new DOMDocument('1.0', 'UTF-8');
		
		$dom_meta->formatOutput = true;
		
		$raiz = $dom_meta->createElement('office:document-meta');
		$dom_meta->appendChild($raiz);
		
		/* Espacio de nombres */
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:office', 'urn:oasis:names:tc:opendocument:xmlns:office:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xlink', 'http://www.w3.org/1999/xlink');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dc', 'http://purl.org/dc/elements/1.1/');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:meta', 'urn:oasis:names:tc:opendocument:xmlns:meta:1.0');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ooo', 'http://openoffice.org/2004/office');
		$raiz->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:grddl', 'http://www.w3.org/2003/g/data-view#');
		
		$raiz->setAttribute('grddl:transformation', 'http://docs.oasis-open.org/office/1.2/xslt/odf2rdf.xsl');
		$raiz->setAttribute('office:version', '1.2');
		
		$meta = $this->createTag($dom_meta, array('xmltag' => 'office:meta', 'subtags' => $this->meta));
		$raiz->appendChild($meta);
		
		return $dom_meta;
	}
	
	public function construir_paquete() {
		$tmp_dir = Gatuf::config('tmp_folder').DIRECTORY_SEPARATOR;
		
		$zip = new ZipArchive();
		
		$this->nombre = $tmp_dir.uniqid().'.ods';
		if ($zip->open($this->nombre, ZipArchive::OVERWRITE) !== true) {
			throw new Exception('Falló al abrir el archivo temporal');
		}
		
		/* El mimetype */
		$zip->addFromString('mimetype', 'application/vnd.oasis.opendocument.spreadsheet');
		
		/* El archivo META-INF/manifest.xml */
		$zip->addEmptyDir('META-INF');
		$manifest = $this->construir_manifest();
		$zip->addFromString('META-INF/manifest.xml', $manifest->saveXML());
		
		/* El archivo meta.xml */
		$meta = $this->construir_meta();
		$zip->addFromString('meta.xml', $meta->saveXML());
		
		/* El archivo content.xml */
		$content = $this->construir_contenido();
		$zip->addFromString('content.xml', $content->saveXML());
		
		$zip->close();
	}
}
