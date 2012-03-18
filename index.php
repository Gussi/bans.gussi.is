<?php
/**
 * Ultraban
 *
 * @author Gussi <gussi@gussi.is>
 */

class ultraban {
	private $db;
	const TYPE_BAN = 0;
	const TYPE_IPBAN = 1;
	const TYPE_WARN = 2;
	const TYPE_KICK = 3;
	const TYPE_FINE = 4;
	const TYPE_UNBAN = 5;
	const TYPE_JAIL = 6;
	const TYPE_PERMABAN = 9;

	public function __construct(PDO $db) {
		$this->db = $db;
	}

	public function get($filters = array(), $options = array()) {
		$param = array();
		$sql = '
			SELECT *
			FROM `banlist`
			WHERE 1=1';

		foreach($filters as $filter => $value) {
			if(empty($value)) continue;
			if(method_exists($this, 'filter_'.$filter)) {
				call_user_func_array(array($this, 'filter_'.$filter), array(&$sql, &$param, $value));
			}
		}

		if(isset($options['order_by'])) {
			$sql .= ' ORDER BY ' . $options['order_by'];
			switch($options['order_dir']) {
				case 'desc':
					$sql .= ' DESC';
					break;
				case 'asc':
				default:
					$sql .= ' ASC';
					break;
			}
		}

		$stm = $this->db->prepare($sql);
		$ret = $stm->execute($param);
		return $stm->fetchAll(PDO::FETCH_ASSOC);
	}

	private function filter_search(&$sql, &$param, $value) {
		$tmp_sql = '';

		if(isset($value['name'])) {
			$tmp_sql = '`name` LIKE ?';
			$param[] = '%'.$value['name'].'%';
		}

		if(isset($value['reason'])) {
			if(!empty($tmp_sql)) {
				$tmp_sql .= ' OR ';
			}
			$tmp_sql .= '`reason` LIKE ?';
			$param[] = '%'.$value['reason'].'%';
		}

		if(isset($value['admin'])) {
			if(!empty($tmp_sql)) {
				$tmp_sql .= ' OR ';
			}
			$tmp_sql .= '`admin` LIKE ?';
			$param[] = '%'.$value['admin'].'%';
		}

		if(!empty($tmp_sql)) {
			$sql .= ' AND ('.$tmp_sql.')';
		}
	}

	private function filter_name(&$sql, &$param, $value) {
		$sql .= ' AND `name` = ?';
		$param[] = $value;
	}

	private function filter_reason(&$sql, &$param, $value) {
		$sql .= ' AND `reason` = ?';
		$param[] = $value;
	}

	private function filter_admin(&$sql, &$param, $value) {
		$sql .= ' AND `admin` = ?';
		$param[] = $value;
	}

	private function filter_type(&$sql, &$param, $value) {
		$sql .= ' AND `type` = ?';
		$param[] = $value;
	}
}

$ultraban = new ultraban(new PDO('mysql:host=localhost;dbname=bukkit', 'bukkit', 'bukkit'));
?>
<!DOCTYPE html>
<html>
<head>
	<title>gussi.is bans</title>
	<meta charset="utf8" />
	<link href="/css/bootstrap.css" rel="stylesheet">
	<style>
		body {
			padding-top: 60px;
		}
	</style>
	<link href="/css/bootstrap-responsive.css" rel="stylesheet">
	<!--[if lt IE 9]>
	  <script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="span12">
				<div class="row">
					<div class="span3">
						<h1>BANNAÐUR!</h1>
						<h6>Þetta eru nú meiru kjánarnir...</h6>
					</div>
					<div class="span9">
						<form method="post" class="well form-inline">
							<input type="text" name="filter_name" placeholder="Nafn" value="<?php isset($_POST['filter_name']) ? print($_POST['filter_name']) : NULL ?>" />
							<input type="text" name="filter_reason" placeholder="Ástæða" value="<?php isset($_POST['filter_reason']) ? print($_POST['filter_reason']) : NULL ?>" />
							<input type="text" name="filter_admin" placeholder="Admin" value="<?php isset($_POST['filter_admin']) ? print($_POST['filter_admin']) : NULL ?>" />
							<input type="submit" class="btn btn-primary" value="Sía"/>
							<input type="reset" class="btn btn-danger" value="Hreinsa" />
						</form>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="span12">
				<table class="table table-striped table-bordered table-condensed">
					<tr>
						<th>#</th>
						<th>Nafn</th>
						<th>Ástæða</th>
						<th>Admin</th>
						<th>Tími</th>
						<th>Týpa</th>
					</tr>
					<?php
					$options = array(
						'order_by'		=> 'time',
						'order_dir'		=> 'desc',
					);
					$filters = array();
					if(isset($_POST)) {
						$filters = array(
							'search'		=> array(),
						);
						foreach($_POST as $key => $value) {
							if(empty($value)) {
								continue;
							}
							if(strstr($key, 'filter_') !== FALSE) {
								$filters['search'][substr($key, 7)] = substr($value, 0, 7);
							}
						}
					}
					$bans = $ultraban->get($filters, $options);
					$type_btn = function($ban) {
						if(time() > $ban['temptime'] && $ban['type'] == Ultraban::TYPE_BAN && $ban['temptime'] != 0) {
							return '<span class="label label-info">ÚTRUNNIÐ</span>';
						}
						switch($ban['type']) {
							case Ultraban::TYPE_BAN:
								if($ban['temptime'] == 0) {
									return '<span class="label label-important">ENDANLEGT</span>';
								}
								return '<span class="label label-important">BANNAÐUR</span>';
								break;
							case Ultraban::TYPE_UNBAN:
								return '<span class="label label-success">SÝKNAÐUR</span>';
								break;
							case Ultraban::TYPE_WARN:
								return '<span class="label label-warning">VIÐVÖRUN</span>';
								break;
						}
					};
					foreach($bans as $ban) {
						$days = floor(($ban['temptime']-$ban['time'])/60/60/24);
						printf("
							<tr>
								<td>%s</td>
								<td>%s</td>
								<td>%s</td>
								<td>%s</td>
								<td>%s%s</td>
								<td>%s</td>
							</tr>"
							, $ban['id']
							, $ban['name']
							, $ban['reason']
							, $ban['admin']
							, date('d.m.Y H:i', $ban['time'])
							, $ban['temptime']
								? sprintf(' í %s dag%s'
									, $days
									, substr($days, -1, 1) == '1'
										? NULL
										: 'a'
								)
								: NULL
							, $type_btn($ban)
						);
					}
					?>
				</table>
			</div>
		</div>
	</div>
	<script src="/js/jquery.js"></script>
	<script src="/js/bootstrap.js"></script>
	<script>
	$('input[type=reset]').bind('click', function(e) {
		e.preventDefault();
		$.each($(this).siblings(), function(key, val) {
			if($(val).attr('type') == 'text') {
				$(val).val('');
			}
		});
	});
	</script>
<body>
</html>
