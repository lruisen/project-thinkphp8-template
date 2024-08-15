<?php

use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\Migrator;

class Install extends Migrator
{
	/**
	 * Change Method.
	 *
	 * Write your reversible migrations using this method.
	 *
	 * More information on writing migrations is available here:
	 * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
	 *
	 * The following commands can be used in this method and Phinx will
	 * automatically reverse them when rolling back:
	 *
	 *    createTable
	 *    renameTable
	 *    addColumn
	 *    renameColumn
	 *    addIndex
	 *    addForeignKey
	 *
	 * Remember to call "create()" or "update()" and NOT "save()" when working
	 * with the Table class.
	 */
	public function change(): void
	{
		$this->createAdmin();
		$this->createAdminGroup();
		$this->createAdminGroupAccess();
		$this->createMenuRule();
		$this->createAttachment();
		$this->createAttachmentCategory();
		$this->createConfig();
		$this->createToken();
	}

	protected function createAdmin(): void
	{
		if ($this->hasTable('admin')) {
			return;
		}

		$table = $this->table('admin', [
			'id' => false,
			'engine' => 'InnoDB',
			'primary_key' => 'id',
			'comment' => '管理员表',
			'row_format' => 'DYNAMIC',
			'collation' => 'utf8mb4_general_ci',
		]);

		$table->addColumn('id', 'integer', ['comment' => 'ID', 'signed' => true, 'identity' => true, 'null' => false])
			->addColumn('account', 'string', ['comment' => '账号', 'limit' => 20, 'null' => false, 'default' => ''])
			->addColumn('password', 'string', ['comment' => '密码', 'limit' => 128, 'null' => false, 'default' => ''])
			->addColumn('avatar', 'string', ['comment' => '头像', 'limit' => 255, 'null' => false, 'default' => ''])
			->addColumn('real_name', 'string', ['comment' => '姓名', 'limit' => 16, 'null' => false, 'default' => ''])
			->addColumn('mobile', 'string', ['comment' => '手机号码', 'limit' => 20, 'null' => false, 'default' => ''])
			->addColumn('last_ip', 'string', ['comment' => '上次登录IP', 'limit' => 16, 'null' => false, 'default' => ''])
			->addColumn('last_time', 'timestamp', ['comment' => '上次登录时间', 'null' => true, 'default' => null])
			->addColumn('login_count', 'integer', ['comment' => '登录次数', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('status', 'boolean', ['comment' => '状态，1正常，0禁用', 'limit' => 1, 'null' => false, 'default' => 1])
			->addColumn('create_time', 'integer', ['comment' => '添加时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('update_time', 'integer', ['comment' => '编辑时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('delete_time', 'integer', ['comment' => '删除时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->addIndex('account', ['unique' => true])
			->addIndex('mobile', ['unique' => true])
			->addIndex('real_name', ['type' => 'BTREE'])
			->create();
	}

	protected function createAdminGroup(): void
	{
		if ($this->hasTable('admin_group')) {
			return;
		}

		$table = $this->table('admin_group', [
			'id' => false,
			'engine' => 'InnoDB',
			'primary_key' => 'id',
			'comment' => '管理分组表',
			'row_format' => 'DYNAMIC',
			'collation' => 'utf8mb4_general_ci',
		]);

		$table->addColumn('id', 'integer', ['comment' => 'ID', 'signed' => true, 'identity' => true, 'null' => false])
			->addColumn('pid', 'integer', ['comment' => '上级分组', 'default' => 0, 'signed' => false, 'null' => false])
			->addColumn('name', 'string', ['limit' => 100, 'default' => '', 'comment' => '组名', 'null' => false])
			->addColumn('rules', 'text', ['null' => true, 'default' => null, 'comment' => '权限规则ID'])
			->addColumn('status', 'boolean', ['comment' => '状态，1正常，0禁用', 'limit' => 1, 'null' => false, 'default' => 1])
			->addColumn('create_time', 'integer', ['comment' => '添加时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('update_time', 'integer', ['comment' => '编辑时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->addIndex('pid', ['type' => 'BTREE'])
			->create();
	}

	protected function createAdminGroupAccess(): void
	{
		if ($this->hasTable('admin_group_access')) {
			return;
		}

		$table = $this->table('admin_group_access', [
			'id' => false,
			'engine' => 'InnoDB',
			'comment' => '管理分组映射表',
			'row_format' => 'DYNAMIC',
			'collation' => 'utf8mb4_general_ci',
		]);

		$table->addColumn('uid', 'integer', ['comment' => '管理员ID', 'signed' => true, 'null' => false])
			->addColumn('group_id', 'integer', ['comment' => '分组ID', 'signed' => true, 'null' => false])
			->addIndex(['uid'], ['type' => 'BTREE'])
			->addIndex(['group_id'], ['type' => 'BTREE'])
			->create();
	}

	protected function createMenuRule(): void
	{
		if ($this->hasTable('menu_rule')) {
			return;
		}

		$table = $this->table('menu_rule', [
			'id' => false,
			'engine' => 'InnoDB',
			'primary_key' => 'id',
			'comment' => '菜单规则表',
			'row_format' => 'DYNAMIC',
			'collation' => 'utf8mb4_general_ci',
		]);

		$table->addColumn('id', 'integer', ['comment' => 'ID', 'signed' => true, 'identity' => true, 'null' => false])
			->addColumn('type', 'boolean', ['comment' => '菜单类型，0目录，1菜单，2权限', 'limit' => 1, 'null' => false, 'default' => 0])
			->addColumn('parent_id', 'integer', ['comment' => '父级id', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('name', 'string', ['comment' => '目录名称', 'limit' => 16, 'null' => false, 'default' => ''])
			->addColumn('icon', 'string', ['comment' => '目录图标', 'limit' => 32, 'null' => false, 'default' => ''])
			->addColumn('route_url', 'string', ['comment' => '路由地址', 'limit' => 64, 'null' => false, 'default' => ''])
			->addColumn('route_name', 'string', ['comment' => '路由名称', 'limit' => 64, 'null' => false, 'default' => ''])
			->addColumn('redirect', 'string', ['comment' => '重定向路由', 'limit' => 64, 'null' => false, 'default' => ''])
			->addColumn('component', 'string', ['comment' => '组件路径', 'limit' => 32, 'null' => false, 'default' => ''])
			->addColumn('sort', 'integer', ['comment' => '目录排序', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('is_root', 'boolean', ['comment' => '是否根路由，1是，0否', 'limit' => 1, 'null' => false, 'default' => 0])
			->addColumn('status', 'boolean', ['comment' => '目录状态，1正常，0停用', 'limit' => 1, 'null' => false, 'default' => 1])
			->addColumn('visible', 'boolean', ['comment' => '显示状态，1显示，0隐藏', 'limit' => 1, 'null' => false, 'default' => 1])
			->addColumn('is_cache', 'boolean', ['comment' => '是否缓存，1是，0否', 'limit' => 1, 'null' => false, 'default' => 0])
			->addColumn('is_frame', 'boolean', ['comment' => '是否外链，1是，0否', 'limit' => 1, 'null' => false, 'default' => 0])
			->addColumn('is_fixed', 'boolean', ['comment' => '是否固定，1是，0否', 'limit' => 1, 'null' => false, 'default' => 0])
			->addColumn('always_show', 'boolean', ['comment' => '是否简化路由，1是，0否', 'limit' => 1, 'null' => false, 'default' => 0])
			->addColumn('permission_code', 'string', ['comment' => '权限标识', 'limit' => 64, 'null' => false, 'default' => ''])
			->addColumn('permission_name', 'string', ['comment' => '权限名称', 'limit' => 64, 'null' => false, 'default' => ''])
			->addColumn('frame_src', 'string', ['comment' => '外链地址', 'limit' => 128, 'null' => false, 'default' => ''])
			->addColumn('is_leaf', 'boolean', ['comment' => '是否存在叶子节点', 'limit' => 1, 'null' => false, 'default' => 0])
			->addColumn('create_time', 'integer', ['comment' => '创建时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('update_time', 'integer', ['comment' => '更新时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->addIndex('parent_id', ['type' => 'BTREE'])
			->addIndex('name', ['type' => 'BTREE'])
			->addIndex('status', ['type' => 'BTREE'])
			->addIndex('route_url', ['unique' => true, 'type' => 'BTREE'])
			->addIndex('permission_code', ['unique' => true, 'type' => 'BTREE'])
			->create();
	}

	protected function createAttachment(): void
	{
		if ($this->hasTable('attachment')) {
			return;
		}

		$table = $this->table('attachment', [
			'id' => false,
			'engine' => 'InnoDB',
			'primary_key' => 'id',
			'comment' => '附件表',
			'row_format' => 'DYNAMIC',
			'collation' => 'utf8mb4_general_ci',
		]);

		$table->addColumn('id', 'integer', ['comment' => 'ID', 'signed' => true, 'identity' => true, 'null' => false])
			->addColumn('cid', 'integer', ['comment' => '文件分类ID', 'default' => 0, 'signed' => false, 'null' => false])
			->addColumn('admin_id', 'integer', ['comment' => '上传管理员ID', 'default' => 0, 'signed' => false, 'null' => false])
			->addColumn('user_id', 'integer', ['comment' => '上传用户ID', 'default' => 0, 'signed' => false, 'null' => false])
			->addColumn('origin_name', 'string', ['limit' => 255, 'default' => '', 'comment' => '原始文件名', 'null' => false])
			->addColumn('new_name', 'string', ['limit' => 255, 'default' => '', 'comment' => '文件名称', 'null' => false])
			->addColumn('file_url', 'string', ['limit' => 255, 'default' => '', 'comment' => '文件路径', 'null' => false])
			->addColumn('thumb_url', 'string', ['limit' => 255, 'default' => '', 'comment' => '压缩图片路径', 'null' => false])
			->addColumn('file_size', 'string', ['limit' => 30, 'default' => '', 'comment' => '附件大小', 'null' => false])
			->addColumn('file_type', 'string', ['limit' => 30, 'default' => '', 'comment' => '附件类型', 'null' => false])
			->addColumn('file_hash', 'string', ['limit' => 64, 'default' => '', 'comment' => '文件hash值', 'null' => false])
			->addColumn('storage', 'string', ['limit' => 10, 'default' => '', 'comment' => '存储类型 ：local|oss|cos|qiniu', 'null' => false])
			->addColumn('topic', 'string', ['limit' => 20, 'default' => '', 'comment' => '上传类型 admin|user|mini_program', 'null' => false])
			->addColumn('create_time', 'integer', ['limit' => 10, 'default' => 0, 'comment' => '上传时间', 'null' => false])
			->addColumn('update_time', 'integer', ['limit' => 10, 'default' => 0, 'comment' => '更新时间', 'null' => false])
			->addIndex('cid', ['type' => 'BTREE'])
			->addIndex('file_hash', ['type' => 'BTREE'])
			->create();
	}

	protected function createAttachmentCategory(): void
	{
		if ($this->hasTable('attachment_category')) {
			return;
		}

		$table = $this->table('attachment_category', [
			'id' => false,
			'engine' => 'InnoDB',
			'primary_key' => 'id',
			'comment' => '附件分类表',
			'row_format' => 'DYNAMIC',
			'collation' => 'utf8mb4_general_ci',
		]);

		$table->addColumn('id', 'integer', ['comment' => 'ID', 'signed' => true, 'identity' => true, 'null' => false])
			->addColumn('name', 'string', ['limit' => 100, 'default' => '', 'comment' => '分类名称', 'null' => false])
			->addColumn('sort', 'integer', ['comment' => '排序', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('create_time', 'integer', ['comment' => '添加时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('update_time', 'integer', ['comment' => '更新时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->create();
	}

	protected function createConfig(): void
	{
		if ($this->hasTable('config')) {
			return;
		}

		$table = $this->table('config', [
			'id' => false,
			'engine' => 'InnoDB',
			'primary_key' => 'id',
			'comment' => '配置表',
			'row_format' => 'DYNAMIC',
			'collation' => 'utf8mb4_general_ci',
		]);

		$table->addColumn('id', 'integer', ['comment' => 'ID', 'signed' => true, 'identity' => true, 'null' => false])
			->addColumn('group', 'string', ['limit' => 30, 'default' => '', 'comment' => '分组', 'null' => false])
			->addColumn('title', 'string', ['limit' => 50, 'default' => '', 'comment' => '变量标题', 'null' => false])
			->addColumn('name', 'string', ['limit' => 30, 'default' => '', 'comment' => '变量名', 'null' => false])
			->addColumn('value', 'text', ['limit' => MysqlAdapter::TEXT_LONG, 'null' => true, 'default' => null, 'comment' => '变量值'])
			->addColumn('content', 'text', ['limit' => MysqlAdapter::TEXT_LONG, 'null' => true, 'default' => null, 'comment' => '字典数据'])
			->addColumn('rule', 'string', ['limit' => 100, 'default' => '', 'comment' => '验证规则', 'null' => false])
			->addColumn('extend', 'string', ['limit' => 255, 'default' => '', 'comment' => '扩展属性', 'null' => false])
			->addColumn('type', 'string', ['limit' => 30, 'default' => '', 'comment' => '变量输入组件类型', 'null' => false])
			->addColumn('sort', 'integer', ['comment' => '排序', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('status', 'boolean', ['comment' => '显示隐藏，1显示，0隐藏', 'limit' => 1, 'null' => false, 'default' => 1])
			->addColumn('create_time', 'integer', ['comment' => '创建时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->addColumn('update_time', 'integer', ['comment' => '更新时间', 'limit' => 10, 'null' => false, 'default' => 0])
			->addIndex('name', ['unique' => true, 'type' => 'BTREE'])
			->addIndex('group', ['type' => 'BTREE'])
			->create();
	}

	protected function createToken(): void
	{
		if ($this->hasTable('token')) {
			return;
		}

		$table = $this->table('token', [
			'id' => false,
			'comment' => '用户Token表',
			'row_format' => 'DYNAMIC',
			'primary_key' => 'token',
			'collation' => 'utf8mb4_unicode_ci',
		]);

		$table->addColumn('id', 'integer', ['comment' => 'ID', 'signed' => true, 'identity' => true, 'null' => false])
			->addColumn('token', 'string', ['limit' => 50, 'default' => '', 'comment' => 'Token', 'null' => false])
			->addColumn('type', 'string', ['limit' => 15, 'default' => '', 'comment' => '类型', 'null' => false])
			->addColumn('user_id', 'integer', ['comment' => '用户ID', 'default' => 0, 'signed' => false, 'null' => false])
			->addColumn('create_time', 'biginteger', ['signed' => false, 'null' => true, 'default' => null, 'comment' => '创建时间'])
			->addColumn('expire_time', 'biginteger', ['signed' => false, 'null' => true, 'default' => null, 'comment' => '过期时间'])
			->addIndex(['type', 'token', 'user_id'], ['type' => 'BTREE'])
			->create();
	}
}
