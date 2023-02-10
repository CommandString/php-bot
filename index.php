<?php

# ______   _____  _     _  _____       ______   _____  _______      _______ _______ _______  _____         _______ _______ _______
# |     \ |_____] |_____| |_____]      |_____] |     |    |            |    |______ |  |  | |_____] |      |_____|    |    |______
# |_____/ |       |     | |            |_____] |_____|    |            |    |______ |  |  | |       |_____ |     |    |    |______

use CommandString\Env\Env;
use Discord\Discord;
use Discord\WebSockets\Intents;
use React\Http\Browser;

require_once __DIR__."/vendor/autoload.php";

# _______ __   _ _    _ _____  ______  _____  __   _ _______ _______ __   _ _______
# |______ | \  |  \  /    |   |_____/ |     | | \  | |  |  | |______ | \  |    |   
# |______ |  \_|   \/   __|__ |    \_ |_____| |  \_| |  |  | |______ |  \_|    |   

$env = Env::createFromJsonFile("./env.json");

# ______  _____ _______ _______  _____   ______ ______ 
# |     \   |   |______ |       |     | |_____/ |     \
# |_____/ __|__ ______| |_____  |_____| |    \_ |_____/

$env->discord = new Discord([
    "token" => $env->token,
    "intents" => Intents::getDefaultIntents()
]);

# _______  _____  _______ _______ _______ __   _ ______  _______
# |       |     | |  |  | |  |  | |_____| | \  | |     \ |______
# |_____  |_____| |  |  | |  |  | |     | |  \_| |_____/ ______|

$env->commands = [
    Commands\Info::class,
    Commands\Evall::class,
    Commands\Resources::class,
    Commands\Manual\Functions::class
];

# _______ _    _ _______ __   _ _______ _______
# |______  \  /  |______ | \  |    |    |______
# |______   \/   |______ |  \_|    |    ______|

$env->events = [
    Events\ready::class,
    Events\Evall::class
];

# _____ __   _ _______ _______  ______ _______ _______ _______ _____  _____  __   _ _______
#   |   | \  |    |    |______ |_____/ |_____| |          |      |   |     | | \  | |______
# __|__ |  \_|    |    |______ |    \_ |     | |_____     |    __|__ |_____| |  \_| ______|

$env->interactions = [
    Interactions\FunctionExamples::class
];

$env->browser = new Browser();

Events\ready::listen();

#  ______ _     _ __   _
# |_____/ |     | | \  |
# |    \_ |_____| |  \_|

$env->discord->run();