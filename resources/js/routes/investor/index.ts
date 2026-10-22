import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/investor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\InvestorController::index
* @see app/Http/Controllers/InvestorController.php:23
* @route '/investor'
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

const investor = {
    index: Object.assign(index, index),
}

export default investor