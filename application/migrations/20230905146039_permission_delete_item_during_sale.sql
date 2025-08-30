-- permission_delete_item_during_sale --
INSERT INTO `phppos_modules_actions` (`action_id`, `module_id`, `action_name_key`, `sort`) VALUES ('can_delete_item_from_sale', 'sales', 'sales_can_delete_item_added_to_sale', 308);
INSERT INTO phppos_permissions_actions (module_id, person_id, action_id)
SELECT DISTINCT phppos_permissions.module_id, phppos_permissions.person_id, action_id
from phppos_permissions
inner join phppos_modules_actions on phppos_permissions.module_id = phppos_modules_actions.module_id
WHERE phppos_permissions.module_id = 'sales' and
action_id = 'can_delete_item_from_sale'
order by module_id, person_id;