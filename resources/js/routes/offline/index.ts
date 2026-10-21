import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\OfflineController::show
* @see app/Http/Controllers/OfflineController.php:39
* @route '/offline'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/offline',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OfflineController::show
* @see app/Http/Controllers/OfflineController.php:39
* @route '/offline'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OfflineController::show
* @see app/Http/Controllers/OfflineController.php:39
* @route '/offline'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OfflineController::show
* @see app/Http/Controllers/OfflineController.php:39
* @route '/offline'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OfflineController::show
* @see app/Http/Controllers/OfflineController.php:39
* @route '/offline'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OfflineController::show
* @see app/Http/Controllers/OfflineController.php:39
* @route '/offline'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OfflineController::show
* @see app/Http/Controllers/OfflineController.php:39
* @route '/offline'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\OfflineController::sync
* @see app/Http/Controllers/OfflineController.php:58
* @route '/offline/sync'
*/
export const sync = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sync.url(options),
    method: 'post',
})

sync.definition = {
    methods: ["post"],
    url: '/offline/sync',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OfflineController::sync
* @see app/Http/Controllers/OfflineController.php:58
* @route '/offline/sync'
*/
sync.url = (options?: RouteQueryOptions) => {
    return sync.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OfflineController::sync
* @see app/Http/Controllers/OfflineController.php:58
* @route '/offline/sync'
*/
sync.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sync.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OfflineController::sync
* @see app/Http/Controllers/OfflineController.php:58
* @route '/offline/sync'
*/
const syncForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: sync.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OfflineController::sync
* @see app/Http/Controllers/OfflineController.php:58
* @route '/offline/sync'
*/
syncForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: sync.url(options),
    method: 'post',
})

sync.form = syncForm

const offline = {
    show: Object.assign(show, show),
    sync: Object.assign(sync, sync),
}

export default offline