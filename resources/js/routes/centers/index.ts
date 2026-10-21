import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/centers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::index
* @see app/Http/Controllers/CostCenterController.php:25
* @route '/centers'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
export const show = (args: { center: string | number | { id: string | number } } | [center: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/centers/{center}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
show.url = (args: { center: string | number | { id: string | number } } | [center: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { center: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { center: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            center: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        center: typeof args.center === 'object'
        ? args.center.id
        : args.center,
    }

    return show.definition.url
            .replace('{center}', parsedArgs.center.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
show.get = (args: { center: string | number | { id: string | number } } | [center: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
show.head = (args: { center: string | number | { id: string | number } } | [center: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
const showForm = (args: { center: string | number | { id: string | number } } | [center: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
showForm.get = (args: { center: string | number | { id: string | number } } | [center: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CostCenterController::show
* @see app/Http/Controllers/CostCenterController.php:39
* @route '/centers/{center}'
*/
showForm.head = (args: { center: string | number | { id: string | number } } | [center: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const centers = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
}

export default centers