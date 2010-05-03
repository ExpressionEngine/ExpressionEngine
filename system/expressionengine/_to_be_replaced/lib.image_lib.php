<?php
/*
=====================================================
 ExpressionEngine - by EllisLab
-----------------------------------------------------
 http://expressionengine.com/
-----------------------------------------------------
 Copyright (c) 2003 - 2010, EllisLab, Inc.
=====================================================
 THIS IS COPYRIGHTED SOFTWARE
 PLEASE READ THE LICENSE AGREEMENT
 http://expressionengine.com/docs/license.html
=====================================================
 File: core.image_lib.php
-----------------------------------------------------
 Purpose: Image Manipulation class
=====================================================
*/
if ( ! defined('EXT'))
	exit('Invalid file request');



class Image_lib {

	var $resize_protocol	= 'gd2';  	// Can be:  imagemagick, netpbm, gd, gd2
	var $maintain_ratio		= TRUE;  	// Whether to maintain aspect ratio when resizing or use hard values
	var $master_dim			= 'height';	// height, width, or auto (auto adjusts the dimension based on whether the image is taller then wider or vice versa)
	var $dynamic_output		= FALSE;	// Whether to send to browser or write to disk
	var $thumb_prefix		= '';
	var $file_path			= '';
	var $file_name			= '';
	var $new_file_name		= '';
	var $size_str			= '';
	var $quality			= '';
	var $dst_width			= '';
	var $dst_height			= '';
	var $rotation			= '';
	var $x_axis				= '';
	var	$y_axis				= '';
	var $wm_x_transp		= 4;
	var $wm_y_transp		= 4;
	
	// Watermark Vars
	
	var $wm_image_path		= '';			// Watermark image
	var $wm_use_font		= TRUE;			// Whether to use the true type font or GD text
	var $wm_font			= 'texb.ttf';	// TT font
	var $wm_font_size		= 17;			// Font size (different versions of GD will eather use points or pixels)
	var $wm_text_size		= 5;			// Native text size if TT font is not used
	var $wm_text			= '';			// Watermark text if graphic is not used
	var $wm_vrt_alignment	= 'T';			// Vertical alignment:	T M B
	var $wm_hor_alignment	= 'L';			// Horizontal alignment: L R C
	var $wm_padding			= 0;			// Padding around text
	var $wm_x_offset		= 0;			// Lets you push text to the right
	var $wm_y_offset		= 0;			 // Lets you push  text down
	var $wm_text_color		= '#990000';	// Text color
	var $wm_use_drop_shadow	= FALSE;		// Enables dropshadow
	var $wm_shadow_color	= '#666666';	// Dropshadow color
	var $wm_shadow_distance	= 2;			// Dropshadow distance
	var $wm_opacity			= 50; 			// Image opacity: 1 - 100  Only works with image
	var $wm_transp_color	= 'ffffff';	// Color of transparency mask of watermark image
	
	
	// Private Vars
	
	var $mime_type			= '';
	var $thumb_name			= '';
	var $src_width			= '';
	var $src_height			= '';
	var $image_type			= '';
	var $full_src_path		= '';
	var $full_dst_path		= '';
	var $create_fnc			= 'imagecreatetruecolor';
	var $copy_fnc			= 'imagecopyresampled';
	var $libpath				= '';
	var $error_msg			= array();


	/** -------------------------------------
	/**  Constructor
	/** -------------------------------------*/
	function Image_lib()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->EE->lang->loadfile('image_lib');				
	}

		
	
	/** -------------------------------------
	/**  Initialize image properties
	/** -------------------------------------*/
	
	// This function resets all class variables to nothing
  
  	function initialize()
  	{
  		$props = array(
  						'thumb_name',
  						'thumb_prefix',
  						'file_path',
  						'file_name',
  						'full_src_path',
  						'new_file_name',
  						'image_type',
  						'size_str',
  						'quality',
  						'src_width',
  						'src_height',
  						'dst_width',
  						'dst_height',
  						'rotation',
  						'x_axis',
  						'y_axis',
  						'create_fnc',
  						'copy_fnc',
  						'wm_image_path',
  						'wm_use_font',
  						'dynamic_output',
  						'wm_font',
  						'wm_font_size',
  						'wm_text',
  						'wm_vrt_alignment',
  						'wm_hor_alignment',
  						'wm_padding',
  						'wm_x_offset',
  						'wm_y_offset',
  						'wm_text_color',
  						'wm_use_drop_shadow',
  						'wm_shadow_color',
  						'wm_shadow_distance',
  						'wm_opacity',
  						'wm_transp_color'  						
  						);
  	
  		foreach ($props as $val)
  		{
  			$this->$val = '';
  		}  		
  	}

	
	
	/** -------------------------------------
	/**  Set image properties
	/** -------------------------------------*/
	
	// This function takes an array as input.  
	// The array must minimally contain these items:
	// 
	// $props['file_path'] = path to image directory
	// $props['file_name'] = name of the image
	//
	// Depending on which image manipulation function is 
	// going to be used other elements will need to be 
	// passed to this function as well.

	function set_properties($props = array())
	{
		/** ------------------------------------
		/**  Turn array indexes into class vars
		/** ------------------------------------*/
	
			if (count($props) > 0)
			{
				foreach ($props as $key => $val)
				{
					$this->$key = $val;
				}
			}

		/** ------------------------------------
		/**  Do we have a file path and file name?
		/** ------------------------------------*/
		
		// If not, bail...
		
		if ($this->file_path == '' OR $this->file_name == '')
		{
			return FALSE;
		}
			
		// Set the full server path
			
		$this->file_path = @realpath($this->file_path).'/';
		$this->file_path = str_replace("\\", "/", $this->file_path); 
				
		/** ------------------------------------
		/**  Assign image properties
		/** ------------------------------------*/
				
		if ( ! $this->get_image_properties($this->file_path.$this->file_name))
		{
			return FALSE;
		}

		/** ------------------------------------
		/**  Should we maintain the image proportions?
		/** ------------------------------------*/
		
		// When creating thumbs, the target width/height
		// might not be in correct proportion with the source
		// image's width/height.  We'll recalculate it here.
		
		if ($this->maintain_ratio === TRUE && ($this->dst_width != '' AND $this->dst_height != ''))
		{
			$this->image_reproportion();
		}
				
		// If the destination width/height was
		// not submitted we will use the values
		// from the actual file
		
		if ($this->dst_width == '')
			$this->dst_width = $this->src_width;
	
		if ($this->dst_height == '')
			$this->dst_height = $this->src_height;
			
		/** ---------------------------------
		/**  Prep the thumbnail indicator
		/** ---------------------------------*/
		
		if ($this->thumb_prefix != '')
		{
			if (strncmp($this->thumb_prefix, '-', 1) !== 0 && strncmp($this->thumb_prefix, '_', 1) !== 0)
			{
				$this->thumb_prefix = "_".$this->thumb_prefix;				
			}
		}

		/** ---------------------------------
		/**  Prep the quality preference
		/** ---------------------------------*/
		
		$this->quality = trim(str_replace("%", "", $this->quality));
		
		if ($this->quality == '' OR $this->quality == 0 OR ! is_numeric($this->quality))
			$this->quality = 90;
		
		/** ---------------------------------
		/**  Set x/y coordinates
		/** ---------------------------------*/
		
		$this->x_axis = ($this->x_axis == '' OR ! is_numeric($this->x_axis)) ? 0 : $this->x_axis;
		$this->y_axis = ($this->y_axis == '' OR ! is_numeric($this->y_axis)) ? 0 : $this->y_axis;
					
		/** ---------------------------------
		/**  Assign the "new file name"
		/** ---------------------------------*/
		
		// This variable is used if we are making a copy of an image.
		// If we are altering the original we'll set $this->new_file_name
		// to the same value as $this->file_name.

		$this->new_file_name = ($this->new_file_name == '' OR ($this->new_file_name == $this->file_name)) ? $this->file_name : $this->new_file_name;

		/** ---------------------------------
		/**  Assign the full source and destination file_paths
		/** ---------------------------------*/
		
		// Split the extension from the file_name.  We do this 
		// in order to insert the thumbnail indicator (if needed)

		$xp	= $this->explode_name($this->new_file_name);
		
		$this->thumb_name	 = $xp['name'].$this->thumb_prefix.$xp['ext'];
		$this->full_src_path = $this->EE->functions->remove_double_slashes($this->file_path.'/'.$this->file_name);		
		$this->full_dst_path = $this->EE->functions->remove_double_slashes($this->file_path.'/'.$xp['name'].$this->thumb_prefix.$xp['ext']);
	
		/** ---------------------------------
		/**  Watermark-related Stuff....
		/** ---------------------------------*/
		
		if ($this->wm_font != '')
		{
			$this->wm_font = APPPATH.'/fonts/'.$this->wm_font;
		}

		if ($this->wm_text_color != '')
		{
			if (strlen($this->wm_text_color) == 6)
			{
				$this->wm_text_color = '#'.$this->wm_text_color;
			}
		}
		
		if ($this->wm_shadow_color != '')
		{
			if (strlen($this->wm_shadow_color) == 6)
			{
				$this->wm_shadow_color = '#'.$this->wm_shadow_color;
			}
		}
	
	
		return TRUE;
	} 

	
	

	/** -------------------------------------
	/**  Image Resize
	/** -------------------------------------*/
	
	// This is a wrapper function that chooses the proper
	// resize function based on the protocol specified
	// in the config file

	function image_resize()
	{
		$protocol = 'image_process_'.$this->resize_protocol;
		
		if (substr($protocol, -3) == 'gd2')
		{
			$protocol = 'image_process_gd';
		}
		
		return $this->$protocol('resize');
	}




	/** -------------------------------------
	/**  Image Crop
	/** -------------------------------------*/
	
	// This is a wrapper function that chooses the proper
	// cropping function based on the protocol specified
	// in the config file

	function image_crop()
	{
		$protocol = 'image_process_'.$this->resize_protocol;
		
		if (substr($protocol, -3) == 'gd2')
		{
			$protocol = 'image_process_gd';
		}
		
		return $this->$protocol('crop');
	}



	/** -------------------------------------
	/**  Image Rotate
	/** -------------------------------------*/
	
	// This is a wrapper function that chooses the proper
	// rotation function based on the protocol specified
	// in the config file
	
	function image_rotate()
	{
		// Allowed rotation values
		
		$degs = array(90, 180, 270, 'vrt', 'hor');	
	
			if ($this->rotation == '' OR ! in_array($this->rotation, $degs))
			{
			$this->set_error('imglib_rotation_angle_required');
			return FALSE;			
			}
	
		/** -------------------------------------
		/**  Reassign the width and height
		/** -------------------------------------*/
	
		if ($this->rotation == 90 OR $this->rotation == 270)
		{
			$this->dst_width	= $this->src_height;
			$this->dst_height	= $this->src_width;
		}
		else
		{
			$this->dst_width	= $this->src_width;
			$this->dst_height	= $this->src_height;
		}
	
		/** -------------------------------------
		/**  Choose resizing function
		/** -------------------------------------*/
		
		if ($this->resize_protocol == 'imagemagick' OR $this->resize_protocol == 'netpbm')
		{
			$protocol = 'image_process_'.$this->resize_protocol;
		
			return $this->$protocol('rotate');
		}
		
 		if ($this->rotation == 'hor' OR $this->rotation == 'vrt')
 		{
			return $this->image_mirror_gd();
 		}
		else
		{		
			return $this->image_rotate_gd();
		}
	}




	/** -------------------------------------
	/**  Image Process - GD 
	/** -------------------------------------*/
	
	// This function will reize or crop

	function image_process_gd($action = 'resize')
	{	
		$v2_override = FALSE;
			
		if ($action == 'crop')
		{
			// If the target width/height match the source then it's pointless to crop, right?
		
			if ($this->dst_width >= $this->src_width AND $this->dst_height >= $this->src_width)
			{
				// We'll return true so the user thinks the process succeeded.
				// It'll be our little secret...

				return TRUE; 
			}
			
			//  Reassign the source width/height if cropping
			
			$this->src_width  = $this->dst_width;
			$this->src_height = $this->dst_height;	
				
			// GD 2.0 has a cropping bug so we'll test for it
			
			if ($this->gd_version() !== FALSE)
			{
				$gd_version = str_replace('0', '', $this->gd_version());			
				$v2_override = ($gd_version == 2) ? TRUE : FALSE;
			}
		}
		else
		{
			// If the target width/height match the source, AND if
			// the new file name is not equal to the old file name
			// we'll simply make a copy of the original with the new name		
		
			if (($this->src_width == $this->dst_width AND $this->src_height == $this->dst_height) AND ($this->file_name != $this->new_file_name))
			{
				if ( ! @copy($this->full_src_path, $this->full_dst_path))
				{
					$this->set_error('imglib_copy_failed');
					return FALSE;
				}
			
				@chmod($this->full_dst_path, FILE_WRITE_MODE);
				return TRUE;
			}
			
			// If resizing the x/y axis must be zero
			
			$this->x_axis = 0;
			$this->y_axis = 0;
		}
		
		
		/** ---------------------------------
		/**  Create the image handle
		/** ---------------------------------*/
		
		if ( ! ($src_img = $this->image_create_gd()))
		{		
			return FALSE;
		}

		/** ---------------------------------
		/**  Create The Image
		/** ---------------------------------*/
				
		if ($this->resize_protocol == 'gd2' AND function_exists('imagecreatetruecolor') AND $v2_override == FALSE)
		{
			$create	= 'imagecreatetruecolor';
			$copy	= 'imagecopyresampled';
		}
		else
		{
			$create	= 'imagecreate';	
			$copy	= 'imagecopyresized';
		}

		$dst_img = $create($this->dst_width, $this->dst_height); 
		
		$copy($dst_img, $src_img, 0, 0, $this->x_axis, $this->y_axis, $this->dst_width, $this->dst_height, $this->src_width, $this->src_height); 

		/** ---------------------------------
		/**  Save the Image
		/** ---------------------------------*/
		if ( ! $this->image_save_gd($dst_img))
		{		
			return FALSE;
		}
		
		/** ---------------------------------
		/**  Kill the file handles
		/** ---------------------------------*/
		imagedestroy($dst_img); 
		imagedestroy($src_img);
		
		// Set the file to 777
		
		@chmod($this->full_dst_path, FILE_WRITE_MODE);			
		
		return TRUE;
	}

	
	

	/** -------------------------------------
	/**  Image process - ImageMagick 
	/** -------------------------------------*/
	
	// This function will resize, crop, or rotate

	function image_process_imagemagick($action = 'resize')
	{				
		/** ---------------------------------
		/**  Do we have a vaild library path?
		/** ---------------------------------*/
				
		if ($this->libpath == '')
		{
			$this->set_error('imglib_libpath_invalid');
			
			return FALSE;
		}
		
		if (substr($this->libpath, -7) != 'convert')
		{
			$this->libpath .= (substr($this->libpath, -1) == '/') ? 'convert' : '/convert';
		}
		
		/** ---------------------------------
		/**  Execute the command
		/** ---------------------------------*/
		
		$cmd = $this->libpath." -quality ".$this->quality;

		if ($action == 'crop')
		{
			$cmd .= " -crop ".$this->dst_width."x".$this->dst_height."+".$this->x_axis."+".$this->y_axis." \"$this->full_src_path\" \"$this->full_dst_path\" 2>&1";
		}
		elseif ($action == 'rotate')
		{
			switch ($this->rotation)
			{
				case 'hor' 	: $angle = '-flop';
					break;
				case 'vrt' 	: $angle = '-flip';
					break;
				default		: $angle = '-rotate '.$this->rotation;
					break;
			}			
		
			$cmd .= " ".$angle." \"$this->full_src_path\" \"$this->full_dst_path\" 2>&1";
		}
		else  // Resize
		{
			$cmd .= " -resize ".$this->dst_width."x".$this->dst_height." \"$this->full_src_path\" \"$this->full_dst_path\" 2>&1";
		}

		$retval = 1;

		@exec($cmd, $output, $retval);
		
		/** ---------------------------------
		/**  Did it work?
		/** ---------------------------------*/
		if ($retval > 0) 
		{
			$this->set_error('imglib_image_process_failed');
			return FALSE;
		}
		
		// Set the file to 777
		
		@chmod($this->full_dst_path, FILE_WRITE_MODE);			
		
		return TRUE;
	}

	

	
	/** -------------------------------------
	/**  Image process - NetPBM 
	/** -------------------------------------*/
	function image_process_netpbm($action = 'resize')
	{
		if ($this->libpath == '')
		{
			$this->set_error('imglib_libpath_invalid');
			return FALSE;
		}
			
		/** ---------------------------------
		/**  Build the resizing command
		/** ---------------------------------*/
		
		switch ($this->image_type)
		{
			case 1 :
						$cmd_in		= 'giftopnm';
						$cmd_out	= 'ppmtogif';
				break;
			case 2 :
						$cmd_in		= 'jpegtopnm';
						$cmd_out	= 'ppmtojpeg';			
				break;
			case 3 :
						$cmd_in		= 'pngtopnm';
						$cmd_out	= 'ppmtopng';
				break;
		}
		
		if ($action == 'crop')
		{
			$cmd_inner = 'pnmcut -left '.$this->x_axis.' -top '.$this->y_axis.' -width '.$this->dst_width.' -height '.$this->dst_height;
		}
		elseif ($action == 'rotate')
		{
			switch ($this->rotation)
			{
				case 90		:	$angle = 'r270';
					break;
				case 180	:	$angle = 'r180';
					break;
				case 270 	:	$angle = 'r90';
					break;
				case 'vrt'	:	$angle = 'tb';
					break;
				case 'hor'	:	$angle = 'lr';
					break;
			}
		
			$cmd_inner = 'pnmflip -'.$angle.' ';
		}
		else // Resize
		{
			$cmd_inner = 'pnmscale -xysize '.$this->dst_width.' '.$this->dst_height;
		}
		
						
		$cmd = $this->libpath.$cmd_in.' '.$this->full_src_path.' | '.$cmd_inner.' | '.$cmd_out.' > '.$this->file_path.'netpbm.tmp';
		
		$retval = 1;
		
		@exec($cmd, $output, $retval);
		
		/** ---------------------------------
		/**  Did it work?
		/** ---------------------------------*/
		if ($retval > 0) 
		{
			$this->set_error('imglib_image_process_failed');
			return FALSE;
		}
		
		// With NetPBM we have to create a temporary image.
		// If you try manipulating the original it fails so
		// we have to rename the temp file.
		
		copy ($this->file_path.'netpbm.tmp', $this->full_dst_path);
		unlink ($this->file_path.'netpbm.tmp');
		@chmod($dst_image, FILE_WRITE_MODE);			
		
		return TRUE;
	}

	
	

	/** -------------------------------------
	/**  Image Rotate - GD 
	/** -------------------------------------*/
	function image_rotate_gd()
	{	
		/** ---------------------------------
		/**  Is Image Rotation Supported?
		/** ---------------------------------*/
		
		// this function is only supported as of PHP 4.3
	
		if ( ! function_exists('imagerotate'))
		{ 
			$this->set_error('imglib_rotate_unsupported');
			return FALSE;
		}
		
		/** ---------------------------------
		/**  Create the image handle
		/** ---------------------------------*/
		
		if ( ! ($src_img = $this->image_create_gd()))
		{		
			return FALSE;
		}

		/** ---------------------------------
		/**  Set the background color
		/** ---------------------------------*/
		
		// This won't work with transparent PNG files so we are
		// going to have to figure out how to determine the color
		// of the alpha channel in a future release.

		$white	= imagecolorallocate($src_img, 255, 255, 255);

		/** ---------------------------------
		/**  Rotate it!
		/** ---------------------------------*/
		$dst_img = imagerotate($src_img, $this->rotation, $white);

		/** ---------------------------------
		/**  Save the Image
		/** ---------------------------------*/
		if ( ! $this->image_save_gd($dst_img))
		{		
			return FALSE;
		}
		
		/** ---------------------------------
		/**  Kill the file handles
		/** ---------------------------------*/
		imagedestroy($dst_img); 
		imagedestroy($src_img);
		
		// Set the file to 777
		
		@chmod($this->full_dst_path, FILE_WRITE_MODE);			
		
		return true;
	}




	/** -------------------------------------
	/**  Create Mirror Image 
	/** -------------------------------------*/
	
	// This function will flip horizontal or vertical
	
	function image_mirror_gd()
	{		
		if ( ! $src_img = $this->image_create_gd())
		{
			return FALSE;
		}
		
		$width  = $this->src_width;
		$height = $this->src_height;

		if ($this->rotation == 'hor')
		{
			for ($i = 0; $i < $height; $i++)
			{		 
				$left  = 0; 
				$right = $width-1; 
	
				while ($left < $right)
				{ 
					$cl = imagecolorat($src_img, $left, $i); 
					$cr = imagecolorat($src_img, $right, $i);
					
					imagesetpixel($src_img, $left, $i, $cr); 
					imagesetpixel($src_img, $right, $i, $cl); 
					
					$left++; 
					$right--; 
				} 
			}
		}
		else
		{
			for ($i = 0; $i < $width; $i++)
			{		 
				$top = 0; 
				$bot = $height-1; 
	
				while ($top < $bot)
				{ 
					$ct = imagecolorat($src_img, $i, $top);
					$cb = imagecolorat($src_img, $i, $bot);
					
					imagesetpixel($src_img, $i, $top, $cb); 
					imagesetpixel($src_img, $i, $bot, $ct); 
					
					$top++; 
					$bot--; 
				} 
			}		
		}
		
		/** ---------------------------------
		/**  Save the Image
		/** ---------------------------------*/
		if ( ! $this->image_save_gd($src_img))
		{		
			return FALSE;
		}
		
		/** ---------------------------------
		/**  Kill the file handles
		/** ---------------------------------*/
		imagedestroy($src_img);
		
		// Set the file to 777
		@chmod($this->full_dst_path, FILE_WRITE_MODE);			
		
		return TRUE;
	}

	
	

	/** -------------------------------------
	/**  Watermark - Graphic Version
	/** -------------------------------------*/
	function image_watermark()
	{
		if ( ! function_exists('imagecolortransparent'))
		{
			$this->set_error('gallery_unsopported_gd');
			return FALSE;		
		}
	
		/** -------------------------------------
		/**  Fetch source image properties
		/** -------------------------------------*/
		
		$this->get_image_properties();
	
		/** -------------------------------------
		/**  Fetch watermark image properties
		/** -------------------------------------*/
	
		$props 		= $this->get_image_properties($this->wm_image_path, TRUE);	
		$wm_type	= $props['image_type'];
		$wm_width	= $props['width'];
		$wm_height	= $props['height'];

		/** -------------------------------------
		/**  Create two image resources
		/** -------------------------------------*/
		$wm_img  = $this->image_create_gd($this->wm_image_path, $wm_type);
		$src_img = $this->image_create_gd($this->full_src_path);
		
		if ($wm_img === FALSE OR $src_img === FALSE)
		{
			return $this->show_error();
		}
		
		/** -------------------------------------
		/**  Reverse the offset if necessary
		/** -------------------------------------*/
		
		// When the image is positioned at the bottom
		// we don't want the vertical offset to push it
		// further down.  We want the reverse, so we'll
		// invert the offset.  Same with the horizontal
		// offset when the image is at the right
	
		if ($this->wm_vrt_alignment == 'B')
			$this->wm_y_offset = $this->wm_y_offset * -1;

		if ($this->wm_hor_alignment == 'R')
			$this->wm_x_offset = $this->wm_x_offset * -1;
	
		/** -------------------------------------
		/**  Set the base x and y axis values
		/** -------------------------------------*/
				
		$x_axis = $this->wm_x_offset + $this->wm_padding;
		$y_axis = $this->wm_y_offset + $this->wm_padding;
		
		/** -------------------------------------
		/**  Set the vertical position
		/** -------------------------------------*/
		switch ($this->wm_vrt_alignment)
		{
			case 'T':
				break;
			case 'M':	$y_axis += ($this->src_height / 2) - ($wm_height / 2);
				break;
			case 'B':	$y_axis += $this->src_height - $wm_height;
				break;
		}
		
		/** -------------------------------------
		/**  Set the horizontal position
		/** -------------------------------------*/
	
		switch ($this->wm_hor_alignment)
		{
			case 'L':
				break;	
			case 'C':	$x_axis += ($this->src_width / 2) - ($wm_width / 2);
				break;
			case 'R':	$x_axis += $this->src_width - $wm_width;
				break;
		}
	
		/** -------------------------------------
		/**  Build the finalized image
		/** -------------------------------------*/
			
		if ($wm_type == 3 AND function_exists('imagealphablending')) 
		{ 
			@imagealphablending($src_img, TRUE);
		} 		
				
		/** -------------------------------------
		/**  Set RGB values for text and shadow
		/** -------------------------------------*/
		
		/*
			$this->wm_transp_color = str_replace('#', '', $this->wm_transp_color);
			$r = hexdec(substr($this->wm_transp_color, 0, 2));
			$g = hexdec(substr($this->wm_transp_color, 2, 2));
			$b = hexdec(substr($this->wm_transp_color, 4, 2));
			$rgb	= imagecolorallocate($src_img, $r, $g, $b);
			imagecolortransparent($wm_img, $rgb);
		*/
		
		imagecolortransparent($wm_img, imagecolorat($wm_img, $this->wm_x_transp, $this->wm_y_transp));
		imagecopymerge($src_img, $wm_img, $x_axis, $y_axis, 0, 0, $wm_width, $wm_height, $this->wm_opacity);
				
		/** -------------------------------------
		/**  Output the image
		/** -------------------------------------*/
	
		if ($this->dynamic_output == TRUE)
		{
			$this->image_display_gd($src_img);
		}
		else
		{
			if ( ! $this->image_save_gd($src_img))
			{
				return FALSE;
			}
		}
		
		imagedestroy($src_img);
		imagedestroy($wm_img);
		
		return TRUE;
	}

	

	/** -------------------------------------
	/**  Watermark - Text Version
	/** -------------------------------------*/
	
	function text_watermark() 
	{
		if ( ! ($src_img = $this->image_create_gd()))
		{		
			return FALSE;
		}
				
		if ($this->wm_use_font == TRUE AND ! file_exists($this->wm_font))
		{
			$this->set_error('gallery_missing_font');
			return FALSE;
		}
		
		/** -------------------------------------
		/**  Fetch source image properties
		/** -------------------------------------*/
		
		$this->get_image_properties();				
		
		/** -------------------------------------
		/**  Set RGB values for text and shadow
		/** -------------------------------------*/
		
		$this->wm_text_color	= str_replace('#', '', $this->wm_text_color);
		$this->wm_shadow_color	= str_replace('#', '', $this->wm_shadow_color);
		
		$R1 = hexdec(substr($this->wm_text_color, 0, 2));
		$G1 = hexdec(substr($this->wm_text_color, 2, 2));
		$B1 = hexdec(substr($this->wm_text_color, 4, 2));

		$R2 = hexdec(substr($this->wm_shadow_color, 0, 2));
		$G2 = hexdec(substr($this->wm_shadow_color, 2, 2));
		$B2 = hexdec(substr($this->wm_shadow_color, 4, 2));
		
		$txt_color	= imagecolorclosest($src_img, $R1, $G1, $B1);
		$drp_color	= imagecolorclosest($src_img, $R2, $G2, $B2);
		
		/** -------------------------------------
		/**  Reverse the vertical offset
		/** -------------------------------------*/
		
		// When the image is positioned at the bottom
		// we don't want the vertical offset to push it
		// further down.  We want the reverse, so we'll
		// invert the offset.  Note: The horizontal
		// offset flips itself automatically
	
		if ($this->wm_vrt_alignment == 'B')
			$this->wm_y_offset = $this->wm_y_offset * -1;
			
		if ($this->wm_hor_alignment == 'R')
			$this->wm_x_offset = $this->wm_x_offset * -1;
		
		/** -------------------------------------
		/**  Set font width and height
		/** -------------------------------------*/
		
		// These are calculated differently depending on
		// whether we are using the true type font or not
				
		if ($this->wm_use_font == TRUE)
		{
			$text_dim = imagettfbbox($this->wm_font_size, 0, $this->wm_font, $this->wm_text);

			// Dimensional array is array(BLx, BLy, BRx, BRy, TRx, TRy, TLx, TLy) 
			$fontwidth  = abs($text_dim['2'] - $text_dim['0']);
			$fontheight = abs($text_dim['5'] - $text_dim['3']);
			
			// set the vertical size of lowercase ligatures that extend
			// below the baseline--we'll use this for bottom aligned text
			$dangler = $text_dim['3'];
			
			// add vertical size of font, but only from top to baseline
			// or top and middle aligned text will appear to be pushed
			// down a height equal to that of "dangling" ligatures
			$this->wm_y_offset += abs($text_dim['5']);		
		}
		else
		{
			$fontwidth  = imagefontwidth($this->wm_text_size) * strlen($this->wm_text);
			$fontheight = imagefontheight($this->wm_text_size);
		}
		
		/** -------------------------------------
		/**  Set base X and Y axis values
		/** -------------------------------------*/
		
		$x_axis = $this->wm_x_offset + $this->wm_padding;
		$y_axis = $this->wm_y_offset + $this->wm_padding;
	
		/** -------------------------------------
		/**  Set verticle alignment
		/** -------------------------------------*/
		
		if ($this->wm_use_drop_shadow == FALSE)
			$this->wm_shadow_distance = 0;

		switch ($this->wm_vrt_alignment) 
		{
			case "T" :
				break;
			case "M":	
						// unlike imagestring(), imagettftext()'s Y coord is for
						// the font's baseline, not its top, so we subtract
						// instead of add
						if ($this->wm_use_font == TRUE)
						{
							$y_axis += ($this->src_height/2) - ($fontheight/2);
						}
						else
						{
							$y_axis += ($this->src_height/2) + ($fontheight/2);							
						}
				break;
			case "B":	
						// unlike imagestring(), imagettftext()'s Y coord is for
						// the font's baseline, not its top, so we only need
						// to subtract half of the value of the amount any
						// ligatures "dangle" below the baseline and not the
						// full height of the font
						if ($this->wm_use_font == TRUE)
						{
							$y_axis += ($this->src_height - $fontheight - $this->wm_shadow_distance - ($dangler/2));	
						}
						else
						{
							$y_axis += ($this->src_height - $fontheight - $this->wm_shadow_distance - ($fontheight/2));							
						}
				break;
		}

		$x_shad = $x_axis + $this->wm_shadow_distance;
		$y_shad = $y_axis + $this->wm_shadow_distance;
		
		/** -------------------------------------
		/**  Set horizontal alignment
		/** -------------------------------------*/
				
		switch ($this->wm_hor_alignment) 
		{
			case "L":
				break;
			case "R":
						if ($this->wm_use_drop_shadow)
							$x_shad += ($this->src_width - $fontwidth);
							$x_axis += ($this->src_width - $fontwidth);
				break;
			case "C":
						if ($this->wm_use_drop_shadow)
							$x_shad += floor(($this->src_width - $fontwidth)/2);
							$x_axis += floor(($this->src_width - $fontwidth)/2);
				break;
		}
		

		/** -------------------------------------
		/**  Add the text to the source image
		/** -------------------------------------*/
		if ($this->wm_use_font)
		{	
			if ($this->wm_use_drop_shadow)
				imagettftext($src_img, $this->wm_font_size, 0, $x_shad, $y_shad, $drp_color, $this->wm_font, $this->wm_text);
				imagettftext($src_img, $this->wm_font_size, 0, $x_axis, $y_axis, $txt_color, $this->wm_font, $this->wm_text);
		}
		else
		{
			if ($this->wm_use_drop_shadow)
				imagestring($src_img, $this->wm_text_size, $x_shad, $y_shad, $this->wm_text, $drp_color);
				imagestring($src_img, $this->wm_text_size, $x_axis, $y_axis, $this->wm_text, $txt_color);
		}

		/** -------------------------------------
		/**  Output the final image
		/** -------------------------------------*/
		if ($this->dynamic_output == TRUE)
		{ 
			$this->image_display_gd($src_img);
		}
		else
		{
			$this->image_save_gd($src_img);
		}
		
		imagedestroy($src_img);
		
		if ($this->dynamic_output == TRUE)
		{
			exit;
		}

		return TRUE;
	}

	

	/** -------------------------------------
	/**  Create Image - GD
	/** -------------------------------------*/
	
	// This simply creates an image resource handle
	// based on the type of image being processed
		
	function image_create_gd($path = '', $image_type = '')
	{
		if ($path == '')
			$path = $this->full_src_path;
			
		if ($image_type == '')
			$image_type = $this->image_type;

		
		switch ($image_type)
		{
			case	 1 :
						if ( ! function_exists('imagecreatefromgif'))
						{
							$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_gif_not_supported'));
							return FALSE;
						}
					
						$im = @imagecreatefromgif($path);
						
						if ($im === FALSE)
						{
							$this->set_error(array('imglib_image_process_failed'));
							return FALSE;
						}
						
						return $im;
				break;
			case 2 :
						if ( ! function_exists('imagecreatefromjpeg'))
						{
							$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_jpg_not_supported'));
							return FALSE;
						}
					
						$im = @imagecreatefromjpeg($path);
						
						if ($im === FALSE)
						{
							$this->set_error(array('imglib_image_process_failed'));
							return FALSE;
						}
						
						return $im;
				break;
			case 3 :
						if ( ! function_exists('imagecreatefrompng'))
						{
							$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_png_not_supported'));				
							return FALSE;
						}
						
						$im = @imagecreatefrompng($path);
						
						if ($im === FALSE)
						{
							$this->set_error(array('imglib_image_process_failed'));
							return FALSE;
						}
						
						return $im;
				break;			
		
		}
		
		$this->set_error(array('imglib_unsupported_imagecreate'));
		return FALSE;
	}



	
	/** ----------------------------------------
	/**  Write image file to disk - GD
	/** ----------------------------------------*/
	
	// Takes an image resource as input and writes the file 
	// to the specified destination

	function image_save_gd($resource)
	{	
		switch ($this->image_type)
		{
			case 1 :
						if ( ! function_exists('imagegif'))
						{
							$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_gif_not_supported'));
							return FALSE;		
						}
						
						@imagegif($resource, $this->full_dst_path);
				break;
			case 2	:
						if ( ! function_exists('imagejpeg'))
						{
							$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_jpg_not_supported'));
							return FALSE;		
						}
						
						@touch($this->full_dst_path); // PHP 4.4.1 bug #35060 - workaround
						@imagejpeg($resource, $this->full_dst_path, $this->quality);
				break;
			case 3	:
						if ( ! function_exists('imagepng'))
						{
							$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_png_not_supported'));
							return FALSE;		
						}
					
						@imagepng($resource, $this->full_dst_path);
				break;
			default		:
							$this->set_error(array('imglib_unsupported_imagecreate'));
							return FALSE;		
				break;		
		}
	
		return TRUE;
	}

	
	
 
	/** -------------------------------------
	/**  Dynamically outputs an image
	/** -------------------------------------*/
	function image_display_gd($resource)
	{
		header("Content-Disposition: filename={$this->file_name};");
		header("Content-Type: {$this->mime_type}");
		header('Content-Transfer-Encoding: binary');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', $this->EE->localize->now).' GMT'); 
	
		switch ($this->image_type)
		{
			case 1 		:	imagegif($resource);
				break;
			case 2		:	imagejpeg($resource, '', $this->quality);
				break;
			case 3		:	imagepng($resource);
				break;
			default		:	echo 'Unable to display the image';
				break;		
		}			
	}

	
	
	/** -------------------------------------
	/**  Reproportion Image Width/Height
	/** -------------------------------------*/
	
	// When creating thumbs, the desired width/height
	// can end up warping the image due to an incorrect 
	// ratio between the full-sized image and the thumb. 
	// 
	// This function lets us reproportion the width/height.
	// if users choose to maintain the aspect ratio when resizing.
	
	function image_reproportion()
	{
		if ( ! is_numeric($this->dst_width) OR ! is_numeric($this->dst_height) OR $this->dst_width == 0 OR $this->dst_height == 0)
			return;
		
		if ( ! is_numeric($this->src_width) OR ! is_numeric($this->src_height) OR $this->src_width == 0 OR $this->src_height == 0)
			return;
	
		if (($this->dst_width >= $this->src_width) AND ($this->dst_height >= $this->src_height))
		{
			$this->dst_width  = $this->src_width;
			$this->dst_height = $this->src_height;
		}
		
		$new_width	= ceil($this->src_width*$this->dst_height/$this->src_height);		
		$new_height	= ceil($this->dst_width*$this->src_height/$this->src_width);
		
		$ratio = (($this->src_height/$this->src_width) - ($this->dst_height/$this->dst_width));

		if ($this->master_dim != 'width' AND $this->master_dim != 'height')
		{
			$this->master_dim = ($ratio < 0) ? 'width' : 'height';
		}
		
		if (($this->dst_width != $new_width) AND ($this->dst_height != $new_height))
		{
			if ($this->master_dim == 'height')
			{
				$this->dst_width = $new_width;
			}
			else
			{
				$this->dst_height = $new_height;
			}
		}
	}

	
	
	
	/** -------------------------------------
	/**  Get Image Properties
	/** -------------------------------------*/
	function get_image_properties($path = '', $return = FALSE)
	{
		// For now we require GD but we should
		// find a way to determine this using IM or NetPBM
		
		if ($path == '')
			$path = $this->full_src_path;
		
		if ( ! function_exists('getimagesize')) 
		{
			$this->set_error('imglib_gd_required_for_props');
			return FALSE;		
		}
				
		if ( ! file_exists($path))
		{
			$this->set_error('imglib_invalid_path');		
			return FALSE;				
		}
		
		$vals = @getimagesize($path);
		
		$types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');
		
		$mime = (isset($types[$vals['2']])) ? 'image/'.$types[$vals['2']] : 'image/jpg';
				
		if ($return == TRUE)
		{
			$v['width']			= $vals['0'];
			$v['height']		= $vals['1'];
			$v['image_type']	= $vals['2'];
			$v['size_str']		= $vals['3']; 
			$v['mime_type']		= $mime;
			
			return $v;
		}
		
		$this->src_width	= $vals['0'];
		$this->src_height	= $vals['1'];
		$this->image_type	= $vals['2'];
		$this->size_str		= $vals['3']; 
		$this->mime_type	= $mime; 
		
		
		return TRUE;
	}




	/** -------------------------------------
	/**  Size Calculator
	/** -------------------------------------*/
	
	/*
			This function takes a known size x width and
			recalculates it to a new size.  Only one
			new variable needs to be known
	
			$props = array(
							'width' 		=> $width,
							'height' 		=> $height,
							'new_width'		=> 40,
							'new_height'	=> ''
						  );	
	*/
	function size_calculator($vals)
	{
		if ( ! is_array($vals))
			return;
			
		$allowed = array('new_width', 'new_height', 'width', 'height');

		foreach ($allowed as $item)
		{
			if ( ! isset($vals[$item]) OR $vals[$item] == '')
				$vals[$item] = 0;
		}
		
		if ($vals['width'] == 0 OR $vals['height'] == 0)
		{
			return $vals;
		}
			
		if ($vals['new_width'] == 0)
		{
			$vals['new_width'] = ceil($vals['width']*$vals['new_height']/$vals['height']);
		}
		elseif ($vals['new_height'] == 0)
		{
			$vals['new_height'] = ceil($vals['new_width']*$vals['height']/$vals['width']);
		}
	
		return $vals;
	}

		

	/** -------------------------------------
	/**  Explode file_name
	/** -------------------------------------*/
	
	// This is a helper function that extracts the extension
	// from the file_name.  This function lets us deal with
	// file_names with multiple periods, like:  my.cool.jpg
	// It returns an associative array with two elements:
	// $array['ext']  = '.jpg';
	// $array['name'] = 'my.cool';

	function explode_name($source_image)
	{
		$ext = strrchr($source_image, '.');
		$name = ($ext === FALSE) ? $source_image : substr($source_image, 0, -strlen($ext));
		
		return array('ext' => $ext, 'name' => $name);
	}	

	


	/** ---------------------------------
	/**  Is GD Installed?
	/** ---------------------------------*/
	
	function gd_loaded()
	{
		if ( ! extension_loaded('gd'))
		{
			if ( ! dl('gd.so'))
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}

	


	/** ---------------------------------
	/**  Fetch GD Version
	/** ---------------------------------*/
	function gd_version()
	{
		if (function_exists('gd_info'))
		{
			$gd_version = @gd_info();
			$gd_version = preg_replace("/\D/", "", $gd_version['GD Version']);
			
			return $gd_version;
		}
		
		return FALSE;
	}

	

	/** -------------------------------------
	/**  Set Error Message
	/** -------------------------------------*/
	function set_error($str)
	{
		if (is_array($str))
		{
			foreach ($str as $val)
			{
				$this->error_msg[] = $this->EE->lang->line($val);
			}		
		}
		else
		{
			$this->error_msg[] = $this->EE->lang->line($str);
		}
	}



	/** -------------------------------------
	/**  Show Error Message
	/** -------------------------------------*/
	function show_error()
	{
		return $this->error_msg;
	}

	

}
// END CLASS

/* End of file lib.image_lib.php */
/* Location: ./system/expressionengine/_to_be_replaced/lib.image_lib.php */