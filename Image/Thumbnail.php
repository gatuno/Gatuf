<?php
/*
# ***** BEGIN LICENSE BLOCK *****
# This file is part of Plume Framework, a simple PHP Application Framework.
# Copyright (C) 2001-2007 Loic d'Anterroches and contributors.
#
# Plume Framework is free software; you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation; either version 2.1 of the License, or
# (at your option) any later version.
#
# Plume Framework is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# ***** END LICENSE BLOCK ***** */

/**
 * Generate the thumbnail of an image.
 *
 * Sample usage.
 *
 * $thumbnail = new Gatuf_Image_Thumbnail($thumbnail_folder, $source_image);
 * if (!$thumbnail->exists()) {
 *     $thumbnail_filename = $thumbnail->generate();
 * }
 *
 */
class Gatuf_Image_Thumbnail {
	protected $dir = ''; /**< Path to the thumbnail folder. */
	protected $filename = ''; /**< Filename of the last created thumbnail. */
	public $source = ''; /**< Full path to the source file. */
	public $size = array(120, 120); /** Max width and heigth of the thumb. */

	/**
	 * Init the thumbnail class. 
	 */
	public function __construct($dir, $source='') {
		$this->dir = $dir;
		$this->source = $source;
	}

	/**
	 * Get the name of a thumbnail from the image name, the image
	 * name can include a path this is just a md5() operation.
	 * If the height and the width are given, the info is used to
	 * generate the name, else not.
	 *
	 * @param array Optional size.
	 * @return string Filename of the thumbnail.
	 */
	public function getName($size=null) {
		$name = md5($this->source);
		if ($size === null) {
			$size = $this->size;
		}
		return $name.'-'.$size[0].'-'.$size[1].'.png';
	}
 
	/**
	 * Get the full path to the thumbnail.
	 */
	public function getPath() {
		return $this->dir.'/'.$this->getName();
	}

	/**
	 * Check if the thumbnail exists.
	 */
	public function exists() {
		return file_exists($this->getPath());
	}

	/**
	 * Get the size of the last created thumbnail
	 * return the same results as the builtin getimagesize PHP function.
	 */
	public function getSize() {
		if (file_exists($this->getPath())) {
			return getimagesize($this->getPath());
		} else {
			return false;
		}
	}

	/**
	 * Create thumbnail of an image, proportions are kept.
	 *
	 * @return mixed Filename or false.
	 */
	public function generate() {
		if (!file_exists($this->source)) {
			return false;
		}
	
		if (($size = @getimagesize($this->source)) === false) {
			return false;
		}
		list($w, $h) = $this->size;
		$type = $size[2];
		$H = $size[1];
		$W = $size[0];
	
		if ($type == '1') {
			$function = 'imagecreatefromgif';
		} elseif ($type == '2') {
			$function = 'imagecreatefromjpeg';
		} elseif ($type == '3') {
			$function = 'imagecreatefrompng';
		} else {
			return false;
		}
		if (!function_exists($function)) {
			return false;
		}
		if (($img = @$function($this->source)) == false) {
			return false;
		}
		// get the zoom factors and the thumbnail height and width
		$rB = $H/$W;
		$rS = $h/$w;
		if (($H > $h) && ($W > $w)) {
			if ($rB > $rS) {
				$height = $h;
				$width  = $height/$rB;
			} else {
				$width = $w;
				$height = $width*$rB;
			}
		} elseif ($H > $h) {
			$height = $h;
			$width  = $height/$rB;
		} elseif ($W > $w) {
			$width = $w;
			$height = $width*$rB;
		} else {
			$height = $H;
			$width  = $W;
		}
		$zx = $W/$width;
		$zy = $H/$height;

		if (($img2=imagecreatetruecolor(round($width), round($height)))===false) {
			return false;
		}
		$this->resampleBicubic($img2, $img, 0, 0, 0, 0, $width, $height, $zx, $zy);
		if (@imagepng($img2, $this->getPath(), 9) === false) {
			return false;
		}
		imagedestroy($img2);
		if (@file_exists($this->getPath())) {
			return $this->getPath();
		} else {
			return false;
		}
	}
	
	/* ========================================================================
	 *   Private functions, should not be called from outside of the class.
	 * ========================================================================
	 */

	/**
	 * Resample the image 
	 * http://www.php.net/manual/en/function.imagecopyresized.php
	 */
	protected function resampleBicubic(
		&$dst,
		&$src,
		$dstx,
		$dsty,
		$srcx,
		$srcy,
		$w,
		$h,
		$zoomX,
		$zoomY=''
	) {
		if (!$zoomY) {
			$zoomY = $zoomX;
		}
		$palsize = ImageColorsTotal($src);
		for ($i = 0; $i<$palsize; $i++) {
			$colors = ImageColorsForIndex($src, $i);
			ImageColorAllocate(
				$dst,
				$colors['red'],
				$colors['green'],
				$colors['blue']
			);
		}
		$zoomX2 = (int)($zoomX/2);
		$zoomY2 = (int)($zoomY/2);
		$dstX = imagesx($dst);
		$dstY = imagesy($dst);
		$srcX = imagesx($src);
		$srcY = imagesy($src);
	
		for ($j=0; $j<($h-$dsty); $j++) {
			$sY = (int)($j*$zoomY) + $srcy;
			$y13 = $sY+$zoomY2;
			$dY = $j+$dsty;
		
			if (($sY >= $srcY) or ($dY >= $dstY) or ($y13 >= $srcY)) {
				break 1;
			}
			for ($i=0; $i<($w-$dstx); $i++) {
				$sX = (int)($i*$zoomX)+$srcx;
				$x34 = $sX+$zoomX2;
				$dX = $i+$dstx;
				if (($sX >= $srcX) or ($dX >= $dstX) or ($x34 >= $srcX)) {
					break 1;
				}
				$c1 = ImageColorsForIndex($src, ImageColorAt($src, $sX, $y13));
				$c2 = ImageColorsForIndex($src, ImageColorAt($src, $sX, $sY));
				$c3 = ImageColorsForIndex($src, ImageColorAt($src, $x34, $y13));
				$c4 = ImageColorsForIndex($src, ImageColorAt($src, $x34, $sY));
		
				$r = ($c1['red']+$c2['red']+$c3['red']+$c4['red'])/4;
				$g = ($c1['green']+$c2['green']+$c3['green']+$c4['green'])/4;
				$b = ($c1['blue']+$c2['blue']+$c3['blue']+$c4['blue'])/4;
		
				ImageSetPixel($dst, $dX, $dY, ImageColorClosest(
					$dst,
					$r,
					$g,
					$b
				));
			}
		}
	}
}
