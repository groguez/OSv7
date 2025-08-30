-- permission_can_set_credit_limit --
INSERT INTO `phppos_modules_actions` (`action_id`, `module_id`, `action_name_key`, `sort`) VALUES ('can_set_credit_limit', 'customers', 'can_set_credit_limit', 600);
INSERT INTO phppos_permissions_actions (module_id, person_id, action_id)
SELECT DISTINCT phppos_permissions.module_id, phppos_permissions.person_id, action_id
from phppos_permissions
inner join phppos_modules_actions on phppos_permissions.module_id = phppos_modules_actions.module_id
WHERE phppos_permissions.module_id = 'customers' and
action_id = 'can_set_credit_limit'
order by module_id, person_id;