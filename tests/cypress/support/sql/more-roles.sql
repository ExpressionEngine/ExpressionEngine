INSERT INTO `exp_roles` (`role_id`, `name`, `short_name`, `description`, `is_locked`) VALUES
	(6, 'Unlocked Extra Role', 'unlocked', '', 'n'),
	(7, 'Locked Role', 'locked', '', 'y');

INSERT INTO `exp_role_settings` (`role_id`, `search_flood_control`) VALUES
	(6, 10),
	(7, 10);

INSERT INTO `exp_permissions` (`role_id`, `site_id`, `permission`) VALUES
	(7, 1, 'can_access_cp'),
	(7, 1, 'can_access_members'),
	(7, 1, 'can_edit_members');