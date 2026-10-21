import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SystemController::show
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/setup/update',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SystemController::show
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemController::show
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SystemController::show
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SystemController::show
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SystemController::show
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SystemController::show
* @see app/Http/Controllers/SystemController.php:16
* @route '/setup/update'
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
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:25
* @route '/setup/update'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

update.definition = {
    methods: ["post"],
    url: '/setup/update',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:25
* @route '/setup/update'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:25
* @route '/setup/update'
*/
update.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:25
* @route '/setup/update'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SystemController::update
* @see app/Http/Controllers/SystemController.php:25
* @route '/setup/update'
*/
updateForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(options),
    method: 'post',
})

update.form = updateForm

const SystemController = { show, update }

export default SystemController