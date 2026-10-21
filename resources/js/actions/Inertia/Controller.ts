import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide'
*/
const Controller2cd573424e34107f979a3a14104e9a95 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller2cd573424e34107f979a3a14104e9a95.url(options),
    method: 'get',
})

Controller2cd573424e34107f979a3a14104e9a95.definition = {
    methods: ["get","head"],
    url: '/guide',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide'
*/
Controller2cd573424e34107f979a3a14104e9a95.url = (options?: RouteQueryOptions) => {
    return Controller2cd573424e34107f979a3a14104e9a95.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide'
*/
Controller2cd573424e34107f979a3a14104e9a95.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller2cd573424e34107f979a3a14104e9a95.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide'
*/
Controller2cd573424e34107f979a3a14104e9a95.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller2cd573424e34107f979a3a14104e9a95.url(options),
    method: 'head',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide'
*/
const Controller2cd573424e34107f979a3a14104e9a95Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controller2cd573424e34107f979a3a14104e9a95.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide'
*/
Controller2cd573424e34107f979a3a14104e9a95Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controller2cd573424e34107f979a3a14104e9a95.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide'
*/
Controller2cd573424e34107f979a3a14104e9a95Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controller2cd573424e34107f979a3a14104e9a95.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Controller2cd573424e34107f979a3a14104e9a95.form = Controller2cd573424e34107f979a3a14104e9a95Form
/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
const Controllerc2f6652df1985751cdc8d095a9b86d80 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerc2f6652df1985751cdc8d095a9b86d80.url(options),
    method: 'get',
})

Controllerc2f6652df1985751cdc8d095a9b86d80.definition = {
    methods: ["get","head"],
    url: '/guide/split',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
Controllerc2f6652df1985751cdc8d095a9b86d80.url = (options?: RouteQueryOptions) => {
    return Controllerc2f6652df1985751cdc8d095a9b86d80.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
Controllerc2f6652df1985751cdc8d095a9b86d80.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerc2f6652df1985751cdc8d095a9b86d80.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
Controllerc2f6652df1985751cdc8d095a9b86d80.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllerc2f6652df1985751cdc8d095a9b86d80.url(options),
    method: 'head',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
const Controllerc2f6652df1985751cdc8d095a9b86d80Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllerc2f6652df1985751cdc8d095a9b86d80.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
Controllerc2f6652df1985751cdc8d095a9b86d80Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllerc2f6652df1985751cdc8d095a9b86d80.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
Controllerc2f6652df1985751cdc8d095a9b86d80Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllerc2f6652df1985751cdc8d095a9b86d80.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Controllerc2f6652df1985751cdc8d095a9b86d80.form = Controllerc2f6652df1985751cdc8d095a9b86d80Form
/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
const Controllere19ee86e9cf603ce1a59a1ec5d21dec5 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

Controllere19ee86e9cf603ce1a59a1ec5d21dec5.definition = {
    methods: ["get","head"],
    url: '/settings/appearance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url = (options?: RouteQueryOptions) => {
    return Controllere19ee86e9cf603ce1a59a1ec5d21dec5.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'head',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
const Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/appearance'
*/
Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Controllere19ee86e9cf603ce1a59a1ec5d21dec5.form = Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form

/**
* Multiple routes resolve to \Inertia\Controller::Controller, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `Controller['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
const Controller = {
    '/guide': Controller2cd573424e34107f979a3a14104e9a95,
    '/guide/split': Controllerc2f6652df1985751cdc8d095a9b86d80,
    '/settings/appearance': Controllere19ee86e9cf603ce1a59a1ec5d21dec5,
}

export default Controller