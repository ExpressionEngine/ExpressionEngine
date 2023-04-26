INSERT INTO `exp_roles` (`role_id`, `name`, `short_name`, `description`, `is_locked`) VALUES
	(6, 'Unlocked Extra Role', 'unlocked', '', 'n'),
	(7, 'Locked Role', 'locked', '', 'y');

INSERT INTO `exp_role_settings` (`role_id`, `search_flood_control`) VALUES
	(6, 10),
	(7, 10);