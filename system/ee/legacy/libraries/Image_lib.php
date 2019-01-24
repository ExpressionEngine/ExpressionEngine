<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2019, EllisLab Corp. (https://ellislab.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Image Manipulation class
 */
class EE_Image_lib {

	var $image_library		= 'gd2';	// Can be:  imagemagick, netpbm, gd, gd2
	var $library_path		= '';
	var $dynamic_output		= FALSE;	// Whether to send to browser or write to disk
	var $source_image		= '';
	var $new_image			= '';
	var $width				= '';
	var $height				= '';
	var $quality			= '90';
	var $create_thumb		= FALSE;
	var $thumb_marker		= '_thumb';
	var $maintain_ratio		= TRUE;		// Whether to maintain aspect ratio when resizing or use hard values
	var $master_dim			= 'auto';	// auto, height, or width.  Determines what to use as the master dimension
	var $rotation_angle		= '';
	var $x_axis				= '';
	var	$y_axis				= '';

	// Watermark Vars
	var $wm_text			= '';			// Watermark text if graphic is not used
	var $wm_type			= 'text';		// Type of watermarking.  Options:  text/overlay
	var $wm_x_transp		= 4;
	var $wm_y_transp		= 4;
	var $wm_overlay_path	= '';			// Watermark image path
	var $wm_font_path		= '';			// TT font
	var $wm_font_size		= 17;			// Font size (different versions of GD will either use points or pixels)
	var $wm_vrt_alignment	= 'B';			// Vertical alignment:   T M B
	var $wm_hor_alignment	= 'C';			// Horizontal alignment: L R C
	var $wm_padding			= 0;			// Padding around text
	var $wm_hor_offset		= 0;			// Lets you push text to the right
	var $wm_vrt_offset		= 0;			// Lets you push  text down
	var $wm_font_color		= '#ffffff';	// Text color
	var $wm_shadow_color	= '';			// Dropshadow color
	var $wm_shadow_distance	= 2;			// Dropshadow distance
	var $wm_opacity			= 50;			// Image opacity: 1 - 100  Only works with image

	// Private Vars
	var $source_folder		= '';
	var $dest_folder		= '';
	var $mime_type			= '';
	var $orig_width			= '';
	var $orig_height		= '';
	var $image_type			= '';
	var $size_str			= '';
	var $full_src_path		= '';
	var $full_dst_path		= '';
	var $create_fnc			= 'imagecreatetruecolor';
	var $copy_fnc			= 'imagecopyresampled';
	var $error_msg			= array();
	var $wm_use_drop_shadow	= FALSE;
	var $wm_use_truetype	= FALSE;
	var $wm_use_opacity		= FALSE;

	/**
	 * Constructor
	 *
	 * @param	string
	 * @return	void
	 */
	public function __construct($props = array())
	{
		if (count($props) > 0)
		{
			$this->initialize($props);
		}

		log_message('debug', "Image Lib Class Initialized");
	}

	/**
	 * Initialize image properties
	 *
	 * Resets values in case this class is used in a loop
	 *
	 * @access	public
	 * @return	void
	 */
	function clear()
	{
		$props = array('source_folder', 'dest_folder', 'source_image', 'full_src_path', 'full_dst_path', 'new_image', 'image_type', 'size_str', 'quality', 'orig_width', 'orig_height', 'rotation_angle', 'x_axis', 'y_axis', 'create_fnc', 'copy_fnc', 'wm_overlay_path', 'wm_use_truetype', 'dynamic_output', 'wm_font_size', 'wm_text', 'wm_vrt_alignment', 'wm_hor_alignment', 'wm_padding', 'wm_hor_offset', 'wm_vrt_offset', 'wm_font_color', 'wm_use_drop_shadow', 'wm_shadow_color', 'wm_shadow_distance', 'wm_opacity');

		foreach ($props as $val)
		{
			$this->$val = '';
		}

		// special consideration for master_dim
		$this->master_dim = 'auto';
	}

	/**
	 * initialize image preferences
	 *
	 * @access	public
	 * @param	array
	 * @return	bool
	 */
	function initialize($props = array())
	{
		/*
		 * Convert array elements into class variables
		 */
		if (count($props) > 0)
		{
			foreach ($props as $key => $val)
			{
				$this->$key = $val;
			}
		}

		/*
		 * Is there a source image?
		 *
		 * If not, there's no reason to continue
		 *
		 */
		if ($this->source_image == '')
		{
			$this->set_error('imglib_source_image_required');
			return FALSE;
		}

		/*
		 * Is getimagesize() Available?
		 *
		 * We use it to determine the image properties (width/height).
		 * Note:  We need to figure out how to determine image
		 * properties using ImageMagick and NetPBM
		 *
		 */
		if ( ! function_exists('getimagesize'))
		{
			$this->set_error('imglib_gd_required_for_props');
			return FALSE;
		}

		$this->image_library = strtolower($this->image_library);

		/*
		 * Set the full server path
		 *
		 * The source image may or may not contain a path.
		 * Either way, we'll try use realpath to generate the
		 * full server path in order to more reliably read it.
		 *
		 */
		if (function_exists('realpath') AND @realpath($this->source_image) !== FALSE)
		{
			$full_source_path = str_replace("\\", "/", realpath($this->source_image));
		}
		else
		{
			$full_source_path = $this->source_image;
		}

		$x = explode('/', $full_source_path);
		$this->source_image = end($x);
		$this->source_folder = str_replace($this->source_image, '', $full_source_path);

		// Set the Image Properties
		if ( ! $this->get_image_properties($this->source_folder.$this->source_image))
		{
			return FALSE;
		}

		/*
		 * Assign the "new" image name/path
		 *
		 * If the user has set a "new_image" name it means
		 * we are making a copy of the source image. If not
		 * it means we are altering the original.  We'll
		 * set the destination filename and path accordingly.
		 *
		 */
		if ($this->new_image == '')
		{
			$this->dest_image = $this->source_image;
			$this->dest_folder = $this->source_folder;
		}
		else
		{
			if (strpos($this->new_image, DIRECTORY_SEPARATOR) === FALSE)
			{
				$this->dest_folder = $this->source_folder;
				$this->dest_image = $this->new_image;
			}
			else
			{
				if (function_exists('realpath') AND @realpath($this->new_image) !== FALSE)
				{
					$full_dest_path = str_replace("\\", "/", realpath($this->new_image));
				}
				else
				{
					$full_dest_path = $this->new_image;
				}

				// Is there a file name?
				if ( ! preg_match("#\.(jpg|jpeg|gif|png)$#i", $full_dest_path))
				{
					$this->dest_folder = $full_dest_path.'/';
					$this->dest_image = $this->source_image;
				}
				else
				{
					$x = explode('/', $full_dest_path);
					$this->dest_image = end($x);
					$this->dest_folder = str_replace($this->dest_image, '', $full_dest_path);
				}
			}
		}

		/*
		 * Compile the finalized filenames/paths
		 *
		 * We'll create two master strings containing the
		 * full server path to the source image and the
		 * full server path to the destination image.
		 * We'll also split the destination image name
		 * so we can insert the thumbnail marker if needed.
		 *
		 */
		if ($this->create_thumb === FALSE OR $this->thumb_marker == '')
		{
			$this->thumb_marker = '';
		}

		$xp	= $this->explode_name($this->dest_image);

		$filename = $xp['name'];
		$file_ext = $xp['ext'];

		$this->full_src_path = $this->source_folder.$this->source_image;
		$this->full_dst_path = $this->dest_folder.$filename.$this->thumb_marker.$file_ext;

		/*
		 * Should we maintain image proportions?
		 *
		 * When creating thumbs or copies, the target width/height
		 * might not be in correct proportion with the source
		 * image's width/height.  We'll recalculate it here.
		 *
		 */
		if ($this->maintain_ratio === TRUE && ($this->width != '' AND $this->height != ''))
		{
			$this->image_reproportion();
		}

		/*
		 * Was a width and height specified?
		 *
		 * If the destination width/height was
		 * not submitted we will use the values
		 * from the actual file
		 *
		 */
		if ($this->width == '')
		{
			$this->width = $this->orig_width;
		}

		if ($this->height == '')
		{
			$this->height = $this->orig_height;
		}

		// Set the quality
		$this->quality = trim(str_replace("%", "", $this->quality));

		if ($this->quality === '' OR $this->quality < 0 OR ! is_numeric($this->quality))
		{
			$this->quality = 90;
		}

		// Set the x/y coordinates
		$this->x_axis = ($this->x_axis == '' OR ! is_numeric($this->x_axis)) ? 0 : $this->x_axis;
		$this->y_axis = ($this->y_axis == '' OR ! is_numeric($this->y_axis)) ? 0 : $this->y_axis;

		// Watermark-related Stuff...
		if ($this->wm_font_color != '')
		{
			if (strlen($this->wm_font_color) == 6)
			{
				$this->wm_font_color = '#'.$this->wm_font_color;
			}
		}

		if ($this->wm_shadow_color != '')
		{
			if (strlen($this->wm_shadow_color) == 6)
			{
				$this->wm_shadow_color = '#'.$this->wm_shadow_color;
			}
		}

		if ($this->wm_overlay_path != '')
		{
			$this->wm_overlay_path = str_replace("\\", "/", realpath($this->wm_overlay_path));
		}

		if (isset($props['wm_opacity']) AND $props['wm_opacity'] != 100)
		{
			$this->wm_use_opacity = TRUE;
		}

		if ($this->wm_shadow_color != '')
		{
			$this->wm_use_drop_shadow = TRUE;
		}

		if ($this->wm_font_path != '')
		{
			$this->wm_use_truetype = TRUE;
		}

		return TRUE;
	}

	/**
	 * Image Resize
	 *
	 * This is a wrapper function that chooses the proper
	 * resize function based on the protocol specified
	 *
	 * @access	public
	 * @return	bool
	 */
	function resize()
	{
		$protocol = 'image_process_'.$this->image_library;

		if (preg_match('/gd2$/i', $protocol))
		{
			$protocol = 'image_process_gd';
		}

		return $this->$protocol('resize');
	}

	/**
	 * Image Crop
	 *
	 * This is a wrapper function that chooses the proper
	 * cropping function based on the protocol specified
	 *
	 * @access	public
	 * @return	bool
	 */
	function crop()
	{
		$protocol = 'image_process_'.$this->image_library;

		if (preg_match('/gd2$/i', $protocol))
		{
			$protocol = 'image_process_gd';
		}

		return $this->$protocol('crop');
	}

	/**
	 * Image Rotate
	 *
	 * This is a wrapper function that chooses the proper
	 * rotation function based on the protocol specified
	 *
	 * @access	public
	 * @return	bool
	 */
	function rotate()
	{
		// Allowed rotation values
		$degs = array(90, 180, 270, 'vrt', 'hor');

		if ($this->rotation_angle == '' OR ! in_array($this->rotation_angle, $degs))
		{
			$this->set_error('imglib_rotation_angle_required');
			return FALSE;
		}

		// Reassign the width and height
		if ($this->rotation_angle == 90 OR $this->rotation_angle == 270)
		{
			$this->width	= $this->orig_height;
			$this->height	= $this->orig_width;
		}
		else
		{
			$this->width	= $this->orig_width;
			$this->height	= $this->orig_height;
		}


		// Choose resizing function
		if ($this->image_library == 'imagemagick' OR $this->image_library == 'netpbm')
		{
			$protocol = 'image_process_'.$this->image_library;

			return $this->$protocol('rotate');
		}

		if ($this->rotation_angle == 'hor' OR $this->rotation_angle == 'vrt')
		{
			return $this->image_mirror_gd();
		}
		else
		{
			return $this->image_rotate_gd();
		}
	}

	/**
	 * Image Process Using GD/GD2
	 *
	 * This function will resize or crop
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function image_process_gd($action = 'resize')
	{
		$v2_override = FALSE;

		// If the target width/height match the source, AND if the new file name is not equal to the old file name
		// we'll simply make a copy of the original with the new name... assuming dynamic rendering is off.
		if ($this->dynamic_output === FALSE)
		{
			if ($this->orig_width == $this->width AND $this->orig_height == $this->height)
			{
				if ($this->source_image != $this->new_image)
				{
					if (@copy($this->full_src_path, $this->full_dst_path))
					{
						@chmod($this->full_dst_path, FILE_WRITE_MODE);
					}
				}

				return TRUE;
			}
		}

		// Let's set up our values based on the action
		if ($action == 'crop')
		{
			//  Reassign the source width/height if cropping
			$this->orig_width  = $this->width;
			$this->orig_height = $this->height;

			// GD 2.0 has a cropping bug so we'll test for it
			if ($this->gd_version() !== FALSE)
			{
				$gd_version = str_replace('0', '', $this->gd_version());
				$v2_override = ($gd_version == 2) ? TRUE : FALSE;
			}
		}
		else
		{
			// If resizing the x/y axis must be zero
			$this->x_axis = 0;
			$this->y_axis = 0;
		}

		//  Create the image handle
		if ( ! ($src_img = $this->image_create_gd()))
		{
			return FALSE;
		}

		//  Create The Image
		//
		//  old conditional which users report cause problems with shared GD libs who report themselves as "2.0 or greater"
		//  it appears that this is no longer the issue that it was in 2004, so we've removed it, retaining it in the comment
		//  below should that ever prove inaccurate.
		//
		//  if ($this->image_library == 'gd2' AND function_exists('imagecreatetruecolor') AND $v2_override == FALSE)
		if ($this->image_library == 'gd2' AND function_exists('imagecreatetruecolor'))
		{
			$create	= 'imagecreatetruecolor';
			$copy	= 'imagecopyresampled';
		}
		else
		{
			$create	= 'imagecreate';
			$copy	= 'imagecopyresized';
		}

		$dst_img = $create($this->width, $this->height);

		// Preserve alpha
		$this->image_preserve_alpha($dst_img, $src_img);

		$copy($dst_img, $src_img, 0, 0, $this->x_axis, $this->y_axis, $this->width, $this->height, $this->orig_width, $this->orig_height);


		//  Show the image
		if ($this->dynamic_output == TRUE)
		{
			$this->image_display_gd($dst_img);
		}
		else
		{
			// Or save it
			if ( ! $this->image_save_gd($dst_img))
			{
				return FALSE;
			}
		}

		//  Kill the file handles
		imagedestroy($dst_img);
		imagedestroy($src_img);

		// Set the file to 777
		@chmod($this->full_dst_path, FILE_WRITE_MODE);

		return TRUE;
	}

	/**
	 * Image Process Using ImageMagick
	 *
	 * This function will resize, crop or rotate
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function image_process_imagemagick($action = 'resize')
	{
		//  Do we have a vaild library path?
		if ($this->library_path == '')
		{
			$this->set_error('imglib_libpath_invalid');
			return FALSE;
		}

		if ( ! $this->arePathsSafe())
		{
			return FALSE;
		}

		if ( ! preg_match("/convert$/i", $this->library_path))
		{
			$this->library_path = rtrim($this->library_path, '/').'/';

			$this->library_path .= 'convert';
		}

		// Execute the command
		$cmd = $this->library_path." -quality ".$this->quality;

		if ($action == 'crop')
		{
			$cmd .= " -crop ".$this->width."x".$this->height."+".$this->x_axis."+".$this->y_axis." ".escapeshellarg($this->full_src_path)." ".escapeshellarg($this->full_dst_path)." 2>&1";
		}
		elseif ($action == 'rotate')
		{
			switch ($this->rotation_angle)
			{
				case 'hor'	: $angle = '-flop';
					break;
				case 'vrt'	: $angle = '-flip';
					break;
				default		: $angle = '-rotate '.$this->rotation_angle;
					break;
			}

			$cmd .= " ".$angle." ".escapeshellarg($this->full_src_path)." ".escapeshellarg($this->full_dst_path)." 2>&1";
		}
		else  // Resize
		{
			$cmd .= " -resize ".$this->width."x".$this->height." ".escapeshellarg($this->full_src_path)." ".escapeshellarg($this->full_dst_path)." 2>&1";
		}

		$retval = 1;

		@exec($cmd, $output, $retval);

		//	Did it work?
		if ($retval > 0)
		{
			$this->set_error('imglib_image_process_failed');
			return FALSE;
		}

		// Set the file to 777
		@chmod($this->full_dst_path, FILE_WRITE_MODE);

		return TRUE;
	}

	/**
	 * Image Process Using NetPBM
	 *
	 * This function will resize, crop or rotate
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function image_process_netpbm($action = 'resize')
	{
		if ($this->library_path == '')
		{
			$this->set_error('imglib_libpath_invalid');
			return FALSE;
		}

		if ( ! $this->arePathsSafe())
		{
			return FALSE;
		}

		//  Build the resizing command
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
			$cmd_inner = 'pnmcut -left '.$this->x_axis.' -top '.$this->y_axis.' -width '.$this->width.' -height '.$this->height;
		}
		elseif ($action == 'rotate')
		{
			switch ($this->rotation_angle)
			{
				case 90		:	$angle = 'r270';
					break;
				case 180	:	$angle = 'r180';
					break;
				case 270	:	$angle = 'r90';
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
			$cmd_inner = 'pnmscale -xysize '.$this->width.' '.$this->height;
		}

		$cmd = $this->library_path.$cmd_in.' '.escapeshellarg($this->full_src_path).' | '.$cmd_inner.' | '.$cmd_out.' > '.escapeshellarg($this->dest_folder.'netpbm.tmp');

		$retval = 1;

		@exec($cmd, $output, $retval);

		//  Did it work?
		if ($retval > 0)
		{
			$this->set_error('imglib_image_process_failed');
			return FALSE;
		}

		// With NetPBM we have to create a temporary image.
		// If you try manipulating the original it fails so
		// we have to rename the temp file.
		copy ($this->dest_folder.'netpbm.tmp', $this->full_dst_path);
		unlink ($this->dest_folder.'netpbm.tmp');
		@chmod($this->full_dst_path, FILE_WRITE_MODE);

		return TRUE;
	}

	/**
	 * Checks path instance variables to make sure they have been properly
	 * sanitized before passing to shell functions
	 *
	 * @return	bool
	 */
	private function arePathsSafe()
	{
		foreach (array('library_path', 'full_src_path',
			'full_dst_path', 'dest_folder') as $path)
		{
			if (ee()->security->sanitize_filename($this->$path, TRUE) !== $this->$path)
			{
				$this->set_error(sprintf(lang('imglib_unsafe_config'), $path));
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Image Rotate Using GD
	 *
	 * @access	public
	 * @return	bool
	 */
	function image_rotate_gd()
	{
		//  Create the image handle
		if ( ! ($src_img = $this->image_create_gd()))
		{
			return FALSE;
		}

		// Set the background color
		// Transparent PNGs will be able to retain their transparency,
		// GIFs and JPEGs will have a white background
		$white	= imagecolorallocate($src_img, 255, 255, 255);

		//  Rotate it!
		$dst_img = imagerotate($src_img, $this->rotation_angle, $white);

		// Preserve transparency
		imagealphablending($dst_img, true);
		imagesavealpha($dst_img, true);

		//  Save the Image
		if ($this->dynamic_output == TRUE)
		{
			$this->image_display_gd($dst_img);
		}
		else
		{
			// Or save it
			if ( ! $this->image_save_gd($dst_img))
			{
				return FALSE;
			}
		}

		//  Kill the file handles
		imagedestroy($dst_img);
		imagedestroy($src_img);

		// Set the file to 777

		@chmod($this->full_dst_path, FILE_WRITE_MODE);

		return true;
	}

	/**
	 * Create Mirror Image using GD
	 *
	 * This function will flip horizontal or vertical
	 *
	 * @access	public
	 * @return	bool
	 */
	function image_mirror_gd()
	{
		if ( ! $src_img = $this->image_create_gd())
		{
			return FALSE;
		}

		$width  = $this->orig_width;
		$height = $this->orig_height;

		$new_img = imagecreatetruecolor($width, $height);

		// Preserve alpha
		$this->image_preserve_alpha($new_img, $src_img);

		$src_x = 0;
		$src_y = 0;
		$src_width = $width;
		$src_height = $height;

		if ($this->rotation_angle == 'hor')
		{
			$src_x = $width - 1;
			$src_width = 0 - $width;
		}
		else
		{
			$src_y = $height - 1;
			$src_height = 0 - $height;
		}

		imagecopyresampled($new_img, $src_img, 0, 0, $src_x, $src_y, $width, $height, $src_width, $src_height);

		//  Show the image
		if ($this->dynamic_output == TRUE)
		{
			$this->image_display_gd($new_img);
		}
		else
		{
			// Or save it
			if ( ! $this->image_save_gd($new_img))
			{
				return FALSE;
			}
		}

		//  Kill the file handles
		imagedestroy($new_img);

		// Set the file to 777
		@chmod($this->full_dst_path, FILE_WRITE_MODE);

		return TRUE;
	}

	/**
	 * Preserves transparencies when working with GIFs and PNGs
	 *
	 * Provided a new image and source image resource, it works with those
	 * already-allocated resources, so it returns void
	 *
	 * @access	public
	 * @param	resource $new_img Destination image resource, will have alpha applied to this
	 * @param	resource $src_img Source image resource for reference
	 */
	function image_preserve_alpha($new_img, $src_img)
	{
		// Preserve transparancies for GIFs and PNGs
		if ($this->image_type == 1 || $this->image_type == 3)
		{
			$src_alpha_index = imagecolortransparent($src_img);

			if ($src_alpha_index >= 0
				AND imagecolorstotal($src_img) > $src_alpha_index)
			{
				// Get color at alpha index
				$alpha_color = imagecolorsforindex($src_img, $src_alpha_index);

				// Get new alpha index in new image resource
				$alpha_index = imagecolorallocate(
					$new_img,
					$alpha_color['red'],
					$alpha_color['green'],
					$alpha_color['blue']
				);

				// Set alpha color as background color and make it transparent
				imagefill($new_img, 0, 0, $alpha_index);
				imagecolortransparent($new_img, $alpha_index);
			}
			else if ($this->image_type == 3)
			{
				imagealphablending($new_img, false);

				// Create a new transparent color for image
				$alpha_color = imagecolorallocatealpha($new_img, 0, 0, 0, 127);

				// Set alpha color as background color and save alpha state
				imagefill($new_img, 0, 0, $alpha_color);
				imagesavealpha($new_img, true);
			}
		}
	}

	/**
	 * Image Watermark
	 *
	 * This is a wrapper function that chooses the type
	 * of watermarking based on the specified preference.
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function watermark()
	{
		if ($this->wm_type == 'overlay')
		{
			return $this->overlay_watermark();
		}
		else
		{
			return $this->text_watermark();
		}
	}

	/**
	 * Watermark - Graphic Version
	 *
	 * @access	public
	 * @return	bool
	 */
	function overlay_watermark()
	{
		if ( ! function_exists('imagecolortransparent'))
		{
			$this->set_error('imglib_gd_required');
			return FALSE;
		}

		//  Fetch source image properties
		$this->get_image_properties();

		//  Fetch watermark image properties
		$props			= $this->get_image_properties($this->wm_overlay_path, TRUE);
		$wm_img_type	= $props['image_type'];
		$wm_width		= $props['width'];
		$wm_height		= $props['height'];

		//  Create two image resources
		$wm_img  = $this->image_create_gd($this->wm_overlay_path, $wm_img_type);
		$src_img = $this->image_create_gd($this->full_src_path);

		// Reverse the offset if necessary
		// When the image is positioned at the bottom
		// we don't want the vertical offset to push it
		// further down.  We want the reverse, so we'll
		// invert the offset.  Same with the horizontal
		// offset when the image is at the right.
		// We'll also make sure padding behaves as it
		// does in CSS, i.e. padding is applied to top
		// and left when aligned in top left, and
		// applied bottom and right when aligned bottom
		// right.

		$this->wm_vrt_alignment = strtoupper(substr($this->wm_vrt_alignment, 0, 1));
		$this->wm_hor_alignment = strtoupper(substr($this->wm_hor_alignment, 0, 1));

		$x_padding = $this->wm_padding;
		$y_padding = $this->wm_padding;

		if ($this->wm_vrt_alignment == 'B')
		{
			$this->wm_vrt_offset = $this->wm_vrt_offset * -1;
			$y_padding = $y_padding * -1;
		}

		if ($this->wm_hor_alignment == 'R')
		{
			$this->wm_hor_offset = $this->wm_hor_offset * -1;
			$x_padding = $x_padding * -1;
		}

		//  Set the base x and y axis values
		$x_axis = $this->wm_hor_offset + $x_padding;
		$y_axis = $this->wm_vrt_offset + $y_padding;

		//  Set the vertical position
		switch ($this->wm_vrt_alignment)
		{
			case 'T':
				break;
			case 'M':
				$y_axis += ($this->orig_height / 2) - ($wm_height / 2);
				break;
			case 'B':
				$y_axis += $this->orig_height - $wm_height;
				break;
		}

		//  Set the horizontal position
		switch ($this->wm_hor_alignment)
		{
			case 'L':
				break;
			case 'C':
				$x_axis += ($this->orig_width / 2) - ($wm_width / 2);
				break;
			case 'R':
				$x_axis += $this->orig_width - $wm_width;
				break;
		}

		//  Build the finalized image
		if ($wm_img_type == 3 AND function_exists('imagealphablending'))
		{
			@imagealphablending($src_img, TRUE);
		}

		// Set RGB values for text and shadow
		$rgba = imagecolorat($wm_img, $this->wm_x_transp, $this->wm_y_transp);
		$alpha = ($rgba & 0x7F000000) >> 24;

		// make a best guess as to whether we're dealing with an image with alpha transparency or no/binary transparency
		if ( ! $this->wm_use_opacity AND $alpha > 0)
		{
			// copy the image directly, the image's alpha transparency being the sole determinant of blending
			imagecopy($src_img, $wm_img, $x_axis, $y_axis, 0, 0, $wm_width, $wm_height);
		}
		else
		{
			// set our RGB value from above to be transparent and merge the images with the specified opacity
			imagecolortransparent($wm_img, imagecolorat($wm_img, $this->wm_x_transp, $this->wm_y_transp));
			imagecopymerge($src_img, $wm_img, $x_axis, $y_axis, 0, 0, $wm_width, $wm_height, $this->wm_opacity);
		}

		//  Output the image
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

	/**
	 * Watermark - Text Version
	 *
	 * @access	public
	 * @return	bool
	 */
	function text_watermark()
	{
		if ( ! ($src_img = $this->image_create_gd()))
		{
			return FALSE;
		}

		if ($this->wm_use_truetype == TRUE AND ! file_exists($this->wm_font_path))
		{
			$this->set_error('imglib_missing_font');
			return FALSE;
		}

		//  Fetch source image properties
		$this->get_image_properties();

		// Set RGB values for text and shadow
		$this->wm_font_color	= str_replace('#', '', $this->wm_font_color);
		$this->wm_shadow_color	= str_replace('#', '', $this->wm_shadow_color);

		$R1 = hexdec(substr($this->wm_font_color, 0, 2));
		$G1 = hexdec(substr($this->wm_font_color, 2, 2));
		$B1 = hexdec(substr($this->wm_font_color, 4, 2));

		$R2 = hexdec(substr($this->wm_shadow_color, 0, 2));
		$G2 = hexdec(substr($this->wm_shadow_color, 2, 2));
		$B2 = hexdec(substr($this->wm_shadow_color, 4, 2));

		$txt_color	= imagecolorclosest($src_img, $R1, $G1, $B1);
		$drp_color	= imagecolorclosest($src_img, $R2, $G2, $B2);

		// Reverse the vertical offset
		// When the image is positioned at the bottom
		// we don't want the vertical offset to push it
		// further down.  We want the reverse, so we'll
		// invert the offset.  Note: The horizontal
		// offset flips itself automatically
		// We'll also make sure padding behaves as it
		// does in CSS, i.e. padding is applied to top
		// and left when aligned in top left, and
		// applied bottom and right when aligned bottom
		// right.

		$this->wm_vrt_alignment = strtoupper(substr($this->wm_vrt_alignment, 0, 1));
		$this->wm_hor_alignment = strtoupper(substr($this->wm_hor_alignment, 0, 1));

		$x_padding = $this->wm_padding;
		$y_padding = $this->wm_padding;

		if ($this->wm_vrt_alignment == 'B')
		{
			$this->wm_vrt_offset = $this->wm_vrt_offset * -1;
			$y_padding = $y_padding * -1;
		}

		if ($this->wm_hor_alignment == 'R')
		{
			$this->wm_hor_offset = $this->wm_hor_offset * -1;
			$x_padding = $x_padding * -1;
		}

		// Set font width and height
		// These are calculated differently depending on
		// whether we are using the true type font or not
		if ($this->wm_use_truetype == TRUE)
		{
			if ($this->wm_font_size == '')
			{
				$this->wm_font_size = '17';
			}

			$fontwidth  = $this->wm_font_size-($this->wm_font_size/4);
			$fontheight = $this->wm_font_size;
			$this->wm_vrt_offset += $this->wm_font_size;
		}
		else
		{
			$fontwidth  = imagefontwidth($this->wm_font_size);
			$fontheight = imagefontheight($this->wm_font_size);
		}

		// Set base X and Y axis values
		$x_axis = (int) $this->wm_hor_offset + (int) $x_padding;
		$y_axis = (int) $this->wm_vrt_offset + (int) $y_padding;

		// Set verticle alignment
		if ($this->wm_use_drop_shadow == FALSE)
			$this->wm_shadow_distance = 0;

		switch ($this->wm_vrt_alignment)
		{
			case "T" :
				break;
			case "M":
				$y_axis += ($this->orig_height/2)+($fontheight/2);
				break;
			case "B":
				$y_axis += ($this->orig_height - $fontheight - $this->wm_shadow_distance - ($fontheight/2));
				break;
		}

		$x_shad = $x_axis + $this->wm_shadow_distance;
		$y_shad = $y_axis + $this->wm_shadow_distance;

		// Set horizontal alignment
		switch ($this->wm_hor_alignment)
		{
			case "L":
				break;
			case "R":
				if ($this->wm_use_drop_shadow){
					$x_shad += ($this->orig_width - $fontwidth*strlen($this->wm_text));
				}
				$x_axis += ($this->orig_width - $fontwidth*strlen($this->wm_text));
				break;
			case "C":
				if ($this->wm_use_drop_shadow)
				{
					$x_shad += floor(($this->orig_width - $fontwidth*strlen($this->wm_text))/2);
				}
				$x_axis += floor(($this->orig_width  -$fontwidth*strlen($this->wm_text))/2);
				break;
		}

		//  Add the text to the source image
		if ($this->wm_use_truetype)
		{
			if ($this->wm_use_drop_shadow)
			{
				imagettftext($src_img, $this->wm_font_size, 0, $x_shad, $y_shad, $drp_color, $this->wm_font_path, $this->wm_text);
			}
			imagettftext($src_img, $this->wm_font_size, 0, $x_axis, $y_axis, $txt_color, $this->wm_font_path, $this->wm_text);
		}
		else
		{
			if ($this->wm_use_drop_shadow)
			{
				imagestring($src_img, $this->wm_font_size, $x_shad, $y_shad, $this->wm_text, $drp_color);
			}
			imagestring($src_img, $this->wm_font_size, $x_axis, $y_axis, $this->wm_text, $txt_color);
		}

		//  Output the final image
		if ($this->dynamic_output == TRUE)
		{
			$this->image_display_gd($src_img);
		}
		else
		{
			$this->image_save_gd($src_img);
		}

		imagedestroy($src_img);

		return TRUE;
	}

	/**
	 * Create Image - GD
	 *
	 * This simply creates an image resource handle
	 * based on the type of image being processed
	 *
	 * @access	public
	 * @param	string
	 * @return	resource
	 */
	function image_create_gd($path = '', $image_type = '')
	{
		if ($path == '')
		{
			$path = $this->full_src_path;
		}

		if ($image_type == '')
		{
			$image_type = $this->image_type;
		}

		switch ($image_type)
		{
			case 1 :
				if ( ! function_exists('imagecreatefromgif'))
				{
					$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_gif_not_supported'));
					return FALSE;
				}

				return imagecreatefromgif($path);
				break;
			case 2 :
				if ( ! function_exists('imagecreatefromjpeg'))
				{
					$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_jpg_not_supported'));
					return FALSE;
				}

				return imagecreatefromjpeg($path);
				break;
			case 3 :
				if ( ! function_exists('imagecreatefrompng'))
				{
					$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_png_not_supported'));
					return FALSE;
				}

				return imagecreatefrompng($path);
				break;
		}

		$this->set_error(array('imglib_unsupported_imagecreate'));
		return FALSE;
	}

	/**
	 * Write image file to disk - GD
	 *
	 * Takes an image resource as input and writes the file
	 * to the specified destination
	 *
	 * @access	public
	 * @param	resource
	 * @return	bool
	 */
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

				if ( ! @imagegif($resource, $this->full_dst_path))
				{
					$this->set_error('imglib_save_failed');
					return FALSE;
				}
				break;
			case 2	:
				if ( ! function_exists('imagejpeg'))
				{
					$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_jpg_not_supported'));
					return FALSE;
				}

				if ( ! @imagejpeg($resource, $this->full_dst_path, $this->quality))
				{
					$this->set_error('imglib_save_failed');
					return FALSE;
				}
				break;
			case 3	:
				if ( ! function_exists('imagepng'))
				{
					$this->set_error(array('imglib_unsupported_imagecreate', 'imglib_png_not_supported'));
					return FALSE;
				}

				// We have a percentage value for quality (0 - 100) but PNGs
				// only accept a quality of 0 - 9, so we must to some math!
				// Additionally, for JPEGs 100 is best quality but for PNGs
				// 0 is best quality. So...for the math, if we want 80% quality
				// then we'd need to do 9 * .8 then subtract that from 9, or
				// just do 9 * .2! So, we'll find that number by doing 100 -
				// quality (percentage math, it's fun for the whole family!)
				$png_quality = round(((100 - $this->quality) / 100) * 9);

				if ( ! @imagepng($resource, $this->full_dst_path, $png_quality))
				{
					$this->set_error('imglib_save_failed');
					return FALSE;
				}
				break;
			default		:
				$this->set_error(array('imglib_unsupported_imagecreate'));
				return FALSE;
				break;
		}

		return TRUE;
	}

	/**
	 * Dynamically outputs an image
	 *
	 * @access	public
	 * @param	resource
	 * @return	void
	 */
	function image_display_gd($resource)
	{
		header("Content-Disposition: filename={$this->source_image};");
		header("Content-Type: {$this->mime_type}");
		header('Content-Transfer-Encoding: binary');
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', time()).' GMT');

		switch ($this->image_type)
		{
			case 1:
				imagegif($resource);
				break;
			case 2:
				imagejpeg($resource, '', $this->quality);
				break;
			case 3:
				imagepng($resource);
				break;
			default:
				echo 'Unable to display the image';
				break;
		}
	}

	/**
	 * Re-proportion Image Width/Height
	 *
	 * When creating thumbs, the desired width/height
	 * can end up warping the image due to an incorrect
	 * ratio between the full-sized image and the thumb.
	 *
	 * This function lets us re-proportion the width/height
	 * if users choose to maintain the aspect ratio when resizing.
	 *
	 * @access	public
	 * @return	void
	 */
	function image_reproportion()
	{
		if ( ! is_numeric($this->width) OR ! is_numeric($this->height) OR $this->width == 0 OR $this->height == 0)
		{
			return;
		}

		if ( ! is_numeric($this->orig_width) OR ! is_numeric($this->orig_height) OR $this->orig_width == 0 OR $this->orig_height == 0)
		{
			return;
		}

		$new_width	= ceil($this->orig_width*$this->height/$this->orig_height);
		$new_height	= ceil($this->width*$this->orig_height/$this->orig_width);

		$ratio = (($this->orig_height/$this->orig_width) - ($this->height/$this->width));

		if ($this->master_dim != 'width' AND $this->master_dim != 'height')
		{
			$this->master_dim = ($ratio < 0) ? 'width' : 'height';
		}

		if (($this->width != $new_width) AND ($this->height != $new_height))
		{
			if ($this->master_dim == 'height')
			{
				$this->width = $new_width;
			}
			else
			{
				$this->height = $new_height;
			}
		}
	}

	/**
	 * Get image properties
	 *
	 * A helper function that gets info about the file
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 */
	function get_image_properties($path = '', $return = FALSE)
	{
		// For now we require GD but we should
		// find a way to determine this using IM or NetPBM

		if ($path == '')
		{
			$path = $this->full_src_path;
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

		$this->orig_width	= $vals['0'];
		$this->orig_height	= $vals['1'];
		$this->image_type	= $vals['2'];
		$this->size_str		= $vals['3'];
		$this->mime_type	= $mime;

		return TRUE;
	}

	/**
	 * Size calculator
	 *
	 * This function takes a known width x height and
	 * recalculates it to a new size.  Only one
	 * new variable needs to be known
	 *
	 *	$props = array(
	 *					'width'			=> $width,
	 *					'height'		=> $height,
	 *					'new_width'		=> 40,
	 *					'new_height'	=> ''
	 *				  );
	 *
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	function size_calculator($vals)
	{
		if ( ! is_array($vals))
		{
			return;
		}

		$allowed = array('new_width', 'new_height', 'width', 'height');

		foreach ($allowed as $item)
		{
			if ( ! isset($vals[$item]) OR $vals[$item] == '')
			{
				$vals[$item] = 0;
			}
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

	/**
	 * Explode source_image
	 *
	 * This is a helper function that extracts the extension
	 * from the source_image.  This function lets us deal with
	 * source_images with multiple periods, like:  my.cool.jpg
	 * It returns an associative array with two elements:
	 * $array['ext']  = '.jpg';
	 * $array['name'] = 'my.cool';
	 *
	 * @access	public
	 * @param	array
	 * @return	array
	 */
	function explode_name($source_image)
	{
		$ext = strrchr($source_image, '.');
		$name = ($ext === FALSE) ? $source_image : substr($source_image, 0, -strlen($ext));

		return array('ext' => $ext, 'name' => $name);
	}

	/**
	 * Is GD Installed?
	 *
	 * @access	public
	 * @return	bool
	 */
	function gd_loaded()
	{
		if ( ! extension_loaded('gd'))
		{
			if (! function_exists('dl') OR ! @dl('gd.so'))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Get GD version
	 *
	 * @access	public
	 * @return	mixed
	 */
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

	/**
	 * Set error message
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function set_error($msg)
	{
		ee()->lang->load('imglib');

		if (is_array($msg))
		{
			foreach ($msg as $val)
			{
				$msg = (ee()->lang->line($val) == FALSE) ? $val : ee()->lang->line($val);
				$this->error_msg[] = $msg;
				log_message('error', $msg);
			}
		}
		else
		{
			$msg = (ee()->lang->line($msg) == FALSE) ? $msg : ee()->lang->line($msg);
			$this->error_msg[] = $msg;
			log_message('error', $msg);
		}
	}

	/**
	 * Show error messages
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function display_errors($open = '<p>', $close = '</p>')
	{
		$str = '';
		foreach ($this->error_msg as $val)
		{
			$str .= $open.$val.$close;
		}

		return $str;
	}

}
// END Image_lib Class

// EOF
