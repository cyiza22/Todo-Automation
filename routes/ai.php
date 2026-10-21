<?php

use App\Mcp\Servers\TodoServer;
use Laravel\Mcp\Facades\Mcp;


Mcp::local('todo', TodoServer::class);
Mcp::web('/mcp/todo', TodoServer::class)->middleware(['auth:sanctum']);