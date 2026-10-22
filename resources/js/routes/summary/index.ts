import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SummaryController::index
* @see app/Http/Controllers/SummaryController.php:15
* @route '/summary'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SummaryController::index
* @see app/Http/Controllers/SummaryController.php:15
* @route '/summary'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SummaryController::index
* @see app/Http/Controllers/SummaryController.php:15
* @route '/summary'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SummaryController::index
* @see app/Http/Controllers/SummaryController.php:15
* @route '/summary'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SummaryController::index
* @see app/Http/Controllers/SummaryController.php:15
* @route '/summary'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SummaryController::index
* @see app/Http/Controllers/SummaryController.php:15
* @route '/summary'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SummaryController::index
* @see app/Http/Controllers/SummaryController.php:15
* @route '/summary'
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

const summary = {
    index: Object.assign(index, index),
}

export default summary