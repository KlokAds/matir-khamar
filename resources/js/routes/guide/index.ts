import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
export const split = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: split.url(options),
    method: 'get',
})

split.definition = {
    methods: ["get","head"],
    url: '/guide/split',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
split.url = (options?: RouteQueryOptions) => {
    return split.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
split.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: split.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
split.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: split.url(options),
    method: 'head',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
const splitForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: split.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
splitForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: split.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/guide/split'
*/
splitForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: split.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

split.form = splitForm

const guide = {
    split: Object.assign(split, split),
}

export default guide