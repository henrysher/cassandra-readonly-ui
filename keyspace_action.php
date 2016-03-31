<?php
	/*
		Cassandra Cluster Admin
		
		@author Sébastien Giroux
		@copyright All rights reserved - 2011
	*/

	require('include/kernel.inc.php');
	require('include/verify_login.inc.php');
	
	$included_header = false;
	$is_valid_action = false;
	
	$action = '';
	if (isset($_GET['action'])) $action = $_GET['action'];
	
	$vw_vars = array();	
		
	/*
		Create a column family
	*/	
	if (isset($_POST['btn_create_columnfamily']) && !READ_ONLY_OPS) {
		$keyspace_name = '';
		if (isset($_GET['keyspace_name'])) {
			$keyspace_name = $_GET['keyspace_name'];
		}
		
		$columnfamily_name = '';
		if (isset($_POST['columnfamily_name'])) {
			$columnfamily_name = $_POST['columnfamily_name'];
		}
		
		$attrs = array();
		
		$column_type = $_POST['column_type'];
		if (!empty($column_type)) $attrs['column_type'] = $column_type;
		
		$comparator_type = $_POST['comparator_type'];
		if (!empty($comparator_type)) $attrs['comparator_type'] = $comparator_type;
		
		if (isset($_POST['subcomparator_type'])) {
			$subcomparator_type = $_POST['subcomparator_type'];
			if (!empty($subcomparator_type) && $column_type == 'Super') $attrs['subcomparator_type'] = $subcomparator_type;
		}
		
		$comment = $_POST['comment'];
		if (!empty($comment)) $attrs['comment'] = $comment;
		
		$row_cache_size = $_POST['row_cache_size'];
		if (!empty($row_cache_size)) $attrs['row_cache_size'] = $row_cache_size;		
		
		$row_cache_save_period_in_seconds = $_POST['row_cache_save_period_in_seconds'];
		if (!empty($row_cache_save_period_in_seconds)) $attrs['row_cache_save_period_in_seconds'] = $row_cache_save_period_in_seconds;		
		
		$key_cache_size = $_POST['key_cache_size'];
		if (!empty($key_cache_size)) $attrs['key_cache_size'] = $key_cache_size;		
		
		$key_cache_save_period_in_seconds = $_POST['key_cache_save_period_in_seconds'];
		if (!empty($key_cache_save_period_in_seconds)) $attrs['key_cache_save_period_in_seconds'] = $key_cache_save_period_in_seconds;		
		
		$read_repair_chance = $_POST['read_repair_chance'];
		if (!empty($read_repair_chance)) $attrs['read_repair_chance'] = $read_repair_chance;	
		
		$gc_grace_seconds = $_POST['gc_grace_seconds'];
		if (!empty($gc_grace_seconds)) $attrs['gc_grace_seconds'] = $gc_grace_seconds;	
		
		$thrift_api_version = $sys_manager->describe_version();
		
		// Cassandra 0.8-
		if (version_compare($thrift_api_version,THRIFT_API_VERSION_FOR_CASSANDRA_1_0,'<')) {
			$memtable_operations_in_millions = $_POST['memtable_operations_in_millions'];
			if (!empty($memtable_operations_in_millions)) $attrs['memtable_operations_in_millions'] = $memtable_operations_in_millions;	
			
			$memtable_throughput_in_mb = $_POST['memtable_throughput_in_mb'];
			if (!empty($memtable_throughput_in_mb)) $attrs['memtable_throughput_in_mb'] = $memtable_throughput_in_mb;	
			
			$memtable_flush_after_mins = $_POST['memtable_flush_after_mins'];
			if (!empty($memtable_flush_after_mins)) $attrs['memtable_flush_after_mins'] = $memtable_flush_after_mins;	
		}
		
		// Cassandra 1.0+
		if (version_compare($thrift_api_version,THRIFT_API_VERSION_FOR_CASSANDRA_1_0,'>=')) {
			$replicate_on_write = $_POST['replicate_on_write'];
			if (!empty($replicate_on_write)) $attrs['replicate_on_write'] = $replicate_on_write;
		
			$key_validation_class = $_POST['key_validation_class'];
			if (!empty($key_validation_class)) $attrs['key_validation_class'] = $key_validation_class;
			
			$key_alias = $_POST['key_alias'];
			if (!empty($key_alias)) $attrs['key_alias'] = $key_alias;
			
			$compaction_strategy = $_POST['compaction_strategy'];
			if (!empty($compaction_strategy)) $attrs['compaction_strategy'] = $compaction_strategy;
			
			$bloom_filter_fp_chance = $_POST['bloom_filter_fp_chance'];
			if (!empty($bloom_filter_fp_chance)) $attrs['bloom_filter_fp_chance'] = $bloom_filter_fp_chance;
		
			$caching = $_POST['caching'];
			if (!empty($caching)) $attrs['caching'] = $caching;
			
			$dclocal_read_repair_chance = $_POST['dclocal_read_repair_chance'];
			if (!empty($dclocal_read_repair_chance)) $attrs['dclocal_read_repair_chance'] = $dclocal_read_repair_chance;

			$merge_shards_chance = $_POST['merge_shards_chance'];
			if (!empty($merge_shards_chance)) $attrs['merge_shards_chance'] = $merge_shards_chance;			

			$row_cache_provider = $_POST['row_cache_provider'];
			if (!empty($row_cache_provider)) $attrs['row_cache_provider'] = $row_cache_provider;
			
			$row_cache_keys_to_save = $_POST['row_cache_keys_to_save'];
			if (!empty($row_cache_keys_to_save)) $attrs['row_cache_keys_to_save'] = $row_cache_keys_to_save;
		}
		
		$default_validation_class = $_POST['default_validation_class'];
		if (!empty($default_validation_class)) $attrs['default_validation_class'] = $default_validation_class;	
		
		$min_compaction_threshold = $_POST['min_compaction_threshold'];
		if (!empty($min_compaction_threshold)) $attrs['min_compaction_threshold'] = $min_compaction_threshold;
		
		$max_compaction_threshold = $_POST['max_compaction_threshold'];		
		if (!empty($max_compaction_threshold)) $attrs['max_compaction_threshold'] = $max_compaction_threshold;
		
		try {
			$time_start = microtime(true);
			$sys_manager->create_column_family($keyspace_name, $columnfamily_name, $attrs);
			$time_end = microtime(true);
			
			$_SESSION['message'] = $columnfamily_name;
			$_SESSION['query_time'] = getQueryTime($time_start,$time_end);
			
			redirect('describe_keyspace.php?keyspace_name='.$keyspace_name.'&create_cf=1');
		}
		catch (Exception $e) {
			$vw_vars['error_message'] = displayErrorMessage('create_columnfamily',array('columnfamily_name' => $columnfamily_name, 'message' => $e->getMessage()));
		}
	}	
	
	/*
		Create a column family
	*/
	
	if ($action == 'create_cf' && !READ_ONLY_OPS) {
		$is_valid_action = true;
	
		$keyspace_name = '';
		if (isset($_GET['keyspace_name'])) {
			$keyspace_name = $_GET['keyspace_name'];
		}
		
		$vw_vars['cluster_name'] = $sys_manager->describe_cluster_name();		
		$vw_vars['keyspace_name'] = $keyspace_name;
		
		$vw_vars['columnfamily_name'] = '';
		$vw_vars['column_type'] = '';
		$vw_vars['comparator_type'] = '';
		$vw_vars['subcomparator_type'] = '';
		$vw_vars['comment'] = '';
		$vw_vars['row_cache_size'] = '';
		$vw_vars['key_cache_size'] = '';
		$vw_vars['read_repair_chance'] = '';
		$vw_vars['gc_grace_seconds'] = '';
		$vw_vars['default_validation_class'] = '';
		$vw_vars['id'] = '';
		$vw_vars['min_compaction_threshold'] = '';
		$vw_vars['max_compaction_threshold'] = '';
		$vw_vars['row_cache_save_period_in_seconds'] = '';
		$vw_vars['key_cache_save_period_in_seconds'] = '';
		
		$thrift_api_version = $sys_manager->describe_version();
		
		// Cassandra 1.0+
		if (version_compare($thrift_api_version,THRIFT_API_VERSION_FOR_CASSANDRA_1_0,'>=')) {
			$vw_vars['replicate_on_write'] = '';
			$vw_vars['key_validation_class'] = '';
			$vw_vars['key_alias'] = '';
			$vw_vars['compaction_strategy'] = '';
			$vw_vars['bloom_filter_fp_chance'] = '';
			$vw_vars['caching'] = '';
			$vw_vars['dclocal_read_repair_chance'] = '';
			$vw_vars['merge_shards_chance'] = '';
			$vw_vars['row_cache_provider'] = '';
			$vw_vars['row_cache_keys_to_save'] = '';
		}
		
		// Cassandra 0.8-
		if (version_compare($thrift_api_version,THRIFT_API_VERSION_FOR_CASSANDRA_1_0,'<')) {
			$vw_vars['memtable_flush_after_mins'] = '';
			$vw_vars['memtable_throughput_in_mb'] = '';
			$vw_vars['memtable_operations_in_millions'] = '';	
		}		
		
		$vw_vars['mode'] = 'create';		
		$vw_vars['thrift_api_version'] = $sys_manager->describe_version();
				
		if (!isset($vw_vars['success_message'])) $vw_vars['success_message'] = '';
		if (!isset($vw_vars['error_message'])) $vw_vars['error_message'] = '';
		
		$included_header = true;
		
		$current_page_title = 'Cassandra Cluster Admin > '.$keyspace_name.' > Create Column Family';
		
		echo getHTML('header.php');
		echo getHTML('create_edit_columnfamily.php',$vw_vars);
	}
		
	/*
		Submit form create a keyspace
	*/
	
	if (isset($_POST['btn_create_keyspace']) && !READ_ONLY_OPS) {
		$keyspace_name = $_POST['keyspace_name'];
		$replication_factor = $_POST['replication_factor'];
		$strategy = $_POST['strategy'];
		
		$attrs = array('replication_factor' => $replication_factor,'strategy_class' => $strategy);
		try {
			$time_start = microtime(true);
			$sys_manager->create_keyspace($keyspace_name, $attrs);
			$time_end = microtime(true);
			
			$vw_vars['success_message'] = displaySuccessMessage('create_keyspace',array('keyspace_name' => $keyspace_name,'query_time' => getQueryTime($time_start,$time_end)));
		}
		catch (Exception $e) {
			$vw_vars['error_message'] = displayErrorMessage('create_keyspace',array('keyspace_name' => $keyspace_name,'message' => $e->getMessage()));
		}
	}
	
	/*
		Create a keyspace
	*/
	
	if ($action == 'create' && !READ_ONLY_OPS) {
		$is_valid_action = true;
	
		$vw_vars['cluster_name'] = $sys_manager->describe_cluster_name();
		$vw_vars['keyspace_name'] = '';
		$vw_vars['replication_factor'] = '';
		$vw_vars['strategy_class'] = '';
		
		$vw_vars['mode'] = 'create';
		
		if (!isset($vw_vars['success_message'])) $vw_vars['success_message'] = '';
		if (!isset($vw_vars['error_message'])) $vw_vars['error_message'] = '';
		
		$included_header = true;
		
		$current_page_title = 'Cassandra Cluster Admin > Create Keyspace';
		
		echo getHTML('header.php');
		echo getHTML('create_edit_keyspace.php',$vw_vars);
	}
	
	/*
		Submit form edit a keyspace
	*/
	
	if (isset($_POST['btn_edit_keyspace']) && !READ_ONLY_OPS) {
		$keyspace_name = $_POST['keyspace_name'];
		$replication_factor = $_POST['replication_factor'];
		$strategy = $_POST['strategy'];
		
		$attrs = array('replication_factor' => $replication_factor,'strategy_class' => $strategy);
				
		try {	
			$time_start = microtime(true);
			$sys_manager->alter_keyspace($keyspace_name, $attrs);
			$time_end = microtime(true);			
			
			$vw_vars['success_message'] = displaySuccessMessage('edit_keyspace',array('keyspace_name' => $keyspace_name,'query_time' => getQueryTime($time_start,$time_end)));
			
			$describe_keyspace = $sys_manager->describe_keyspace($keyspace_name);
			$old_replication_factor = $describe_keyspace->replication_factor;			
			
			$old_replication_factor = $describe_keyspace->replication_factor;
			$strategy_options = $describe_keyspace->strategy_options;
			if ($old_replication_factor == '' && isset($strategy_options['replication_factor'])) $old_replication_factor = $strategy_options['replication_factor'];		
			if ($old_replication_factor == '') $old_replication_factor = 1;						
			
			$new_replication_factor = $replication_factor;
			
			// Display tips about the replication factor that has been increased
			if ($old_replication_factor < $new_replication_factor) {
				$vw_vars['success_message'] .= displayInfoMessage('edit_keyspace_increased_replication_factor',array());
			}
			// Display tips about the replication factor that has been decreased
			elseif ($old_replication_factor > $new_replication_factor) {
				$vw_vars['success_message'] .= displayInfoMessage('edit_keyspace_decreased_replication_factor',array());			
			}
		}
		catch (Exception $e) {
			$vw_vars['error_message'] = displayErrorMessage('edit_keyspace',array('keyspace_name' => $keyspace_name,'message' => $e->getMessage()));
		}
	}
	
	/*
		Edit a keyspace
	*/
	
	if ($action == 'edit' && !READ_ONLY_OPS) {
		$is_valid_action = true;
	
		$keyspace_name = '';
		if (isset($_GET['keyspace_name'])) {
			$keyspace_name = $_GET['keyspace_name'];
		}
	
		$current_page_title = 'Cassandra Cluster Admin > Edit Keyspace '.$keyspace_name;
	
		$included_header = true;
		echo getHTML('header.php');
	
		// Keyspace name was empty
		if ($keyspace_name == '') {
			echo displayErrorMessage('keyspace_name_must_be_specified');
		}
		else {	
			$found = true;
			
			try {
				$describe_keyspace = $sys_manager->describe_keyspace($keyspace_name);
			}
			catch(cassandra\NotFoundException $e) {
				$found = false;
			}
			
			// Found the keyspace
			if ($found) {
				$vw_vars['cluster_name'] = $sys_manager->describe_cluster_name();
				$vw_vars['keyspace_name'] = $keyspace_name;
				
				$strategy_options = $describe_keyspace->strategy_options;
			
				$replication_factor = $describe_keyspace->replication_factor;
				if ($replication_factor == '' && isset($strategy_options['replication_factor'])) $replication_factor = $strategy_options['replication_factor'];		
				if ($replication_factor == '') $replication_factor = 1;
				$vw_vars['replication_factor'] = $replication_factor;			
				
				$vw_vars['strategy_class'] = $describe_keyspace->strategy_class;
				
				$vw_vars['mode'] = 'edit';
				
				if (!isset($vw_vars['success_message'])) $vw_vars['success_message'] = '';
				if (!isset($vw_vars['error_message'])) $vw_vars['error_message'] = '';
				
				echo getHTML('create_edit_keyspace.php',$vw_vars);
			}
			// Keyspace name wasn't found
			else {				
				echo displayErrorMessage('keyspace_doesnt_exists',array('keyspace_name' => $keyspace_name));
			}
		}
	}
	
	/*
		Drop a keyspace
	*/
	
	if ($action == 'drop' && !READ_ONLY_OPS) {
		$is_valid_action = true;
	
		$keyspace_name = '';
		if (isset($_GET['keyspace_name'])) {
			$keyspace_name = $_GET['keyspace_name'];
		}
		
		try {
			$time_start = microtime(true);			
			$sys_manager->drop_keyspace($keyspace_name);
			$time_end = microtime(true);
			
			$_SESSION['success_message'] = 'drop_keyspace';
			$_SESSION['keyspace_name'] = $keyspace_name;
			$_SESSION['query_time'] = getQueryTime($time_start,$time_end);
			
			redirect('index.php?success_message=drop_keyspace');
		}
		catch (Exception $e) {
			$_SESSION['error_message'] = 'drop_keyspace';
			$_SESSION['keyspace_name'] = $keyspace_name;
			$_SESSION['message'] = $e->getMessage();
			redirect('index.php?error_message=drop_keyspace');
		}
	}
	
	if (!$included_header) {
		echo getHTML('header.php');
		
		if (!$is_valid_action) {
			// No action specified
			if (empty($action)) {
				echo displayErrorMessage('no_action_specified');
			}
			// Invalid action specified
			else {
				echo displayErrorMessage('invalid_action_specified',array('action' => $action));
			}
		}
	}
	
	echo getHTML('footer.php');
?>
