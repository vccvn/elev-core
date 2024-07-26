<?php
function __call__($args = [])
{

    if (isset($args[1])) {
        $a = $args;
        $f = $args[1];
        array_shift($a);
        array_shift($a);
        if ($f != 'test_speed' && isset($a[0]) && is_callable($a[0])) {
            $f2 = $a[0];
            array_shift($a);
            for ($i = 0; $i < count($a); $i++) {
                $a[$i] = str_replace('+', ' ', $a[$i]);
            }
            $param = [call_user_func_array($f2, $a)];
        } else {
            $param = $a;
        }

        if (is_callable($f)) {
            return call_user_func_array($f, $param);
        } else {
            switch ($f) {
                case "make":
                case 'create':
                case 'generate':
                case 'm':
                case 'g':
                case 'add':
                case 'c':
                    if (isset($param[0])) {
                        $t = array_shift($param);
                        $p = get_args_params($param);
                        $args = array_merge([$p['params']], $p['args']);
                        if ($t == 'table') {
                            call_user_func_array("create_{$t}", $args);
                        } elseif (in_array(strtolower($t), ['provider', 'serviceproviders'])) {
                            call_user_func_array("create_provider", $args);
                        } else {
                            $t = strtolower($t);
                            switch ($t) {
                                case 'r':
                                case 'repository':
                                    call_user_func_array("make_repository", $args);
                                    break;
                                case 'm':
                                case 'model':
                                    call_user_func_array("make_model", $args);
                                    break;
                                case 'mm':
                                case 'modelmongo':
                                case 'mongomodel':
                                    $args[0]['modelType'] = 'mongo';
                                    call_user_func_array("make_model", $args);
                                    break;
                                case 'mk':
                                case 'mask':
                                    call_user_func_array("make_mask", $args);
                                    break;
                                case 'c':
                                case 'ctrl':
                                case 'controller':
                                    call_user_func_array("make_controller", $args);
                                    break;
                                case 'v':
                                case 'validator':
                                case 'validate':
                                    call_user_func_array("make_validator", $args);
                                    break;

                                case 'cmd':
                                case 'cl':
                                case 'command':
                                case 'command-line':
                                    call_user_func_array("make_command", $args);
                                    break;

                                case 'j':
                                case 'jm':
                                case 'jsonmodule':
                                case 'jmodule':
                                    call_user_func_array("make_json_module", $args);
                                    break;


                                case 's':
                                case 'service':
                                case 'serv':
                                case 'sv':
                                    call_user_func_array("create_service", $args);
                                    break;
                                case 'route':
                                case 'router':
                                    call_user_func_array("make_route", $args);
                                    break;
                                case 'dto':
                                case 'd':
                                case 'rd':
                                    call_user_func_array("make_dto", $args);
                                    break;
                                default:
                                    echo "what you want to make? \n\t - c, controller\n\t - m, -model\n\t - mk, mask\n\t - r, repository\n\t - v, validator\n\t - cmd, cl, command, command-line\n\t - s, service, sv, serv";
                                    break;
                            }
                        }
                    } else {
                        echo "what you want to make? \n\t - c, controller\n\t - m, -model\n\t - mk, mask\n\t - r, repository\n\t - v, validator\n\t - cmd, cl, command, command-line\n\t - s, service, sv, serv";
                    }
                    break;

                case 'alter':
                    if (isset($param[0])) {
                        $t = array_shift($param);
                        $p = get_args_params($param);
                        $args = array_merge([$p['params']], $p['args']);
                        if ($t == 'table') {
                            call_user_func_array("alter_{$t}", $args);
                        }
                    }
                    break;
                case 'list':
                case 'l':
                case 'ds':
                    $t = array_shift($param);
                    $p = get_args_params($param);
                    $args = array_merge([$p['params']], $p['args']);
                    call_user_func_array('show_list', $args);
                    break;
                case 'update':
                case 'ud':
                    if (isset($param[0])) {
                        $t = strtolower(array_shift($param));
                        $p = get_args_params($param);
                        $args = array_merge([$p['params']], $p['args']);
                        if (in_array($t, ['jf', 'json-form', 'json_form', 'jsonform', 'jform'])) {
                            call_user_func_array("update_json_form", $args);
                        }
                    }
                    break;
                default:
                    echo "What do you want to do?\n\t make or create something\n\t run command exists";
            }
        }
    }
}
