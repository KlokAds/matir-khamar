import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\HerdController::index
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/breeding',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\HerdController::index
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::index
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\HerdController::index
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\HerdController::index
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\HerdController::index
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\HerdController::index
* @see app/Http/Controllers/HerdController.php:25
* @route '/breeding'
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

const breeding = {
    index: Object.assign(index, index),
}

export default breeding