<?php

namespace app\admin\model;

use think\facade\Db;
use think\Model;

class AdminModel extends Model
{
	protected $name = 'admin';

	/**
	 * 追加属性
	 */
	protected $append = [
		'group_arr',
		'group_name_arr',
	];

	public function getGroupArrAttr($value, $row): array
	{
		return Db::name('admin_group_access')
			->where('uid', $row['id'])
			->column('group_id');
	}

	public function getGroupNameArrAttr($value, $row): array
	{
		$groupAccess = db('admin_group_access')
			->where('uid', $row['id'])
			->column('group_id');

		return AdminGroupModel::whereIn('id', $groupAccess)->column('name');
	}
}