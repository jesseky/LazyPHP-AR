<?php

if( !defined('IN') ) die('bad request');

class coreController 
{
	function __construct()
	{
		// load model functions
		$model_function_file = AROOT . 'model' . DS . g('c') . '.function.php';
		if( file_exists( $model_function_file ) )  
			require_once( $model_function_file );
		else
		{
			$cmodel = CROOT . 'model' . DS . g('c') . '.function.php';
			if( file_exists( $cmodel ) )  require_once( $cmodel );
		}

		// load model classes
		spl_autoload_register(function ($name) {
			$fname = AROOT . 'model' . DS . $name . '.class.php';
			if ( file_exists( $fname ) ) 
			{
				require_once( CROOT . 'model' . DS . 'core.class.php' );
				require_once( $fname );
			}
		});
	}
	
	public function index()
	{
		// 
	} 
}
