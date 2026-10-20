<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateTodoTool;
use App\Mcp\Tools\DeleteTodoTool;
use App\Mcp\Tools\ListTodosTool;
use App\Mcp\Tools\UpdateTodoTool;
use Laravel\Mcp\Server;

class TodoServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Todo Server';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.0.1';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        You are an intelligent task-management agent. You can fetch, filter, create, update and delete tasks. 
        Always clarify dates when adding a task and summarize actions clearly.
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        CreateTodoTool::class,
        UpdateTodoTool::class,
        DeleteTodoTool::class,
        ListTodosTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<\Laravel\Mcp\Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
