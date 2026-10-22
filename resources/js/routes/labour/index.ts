import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import logs from './logs'
import workers from './workers'
/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/labour',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LabourController::index
* @see app/Http/Controllers/LabourController.php:22
* @route '/labour'
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

const labour = {
    logs: Object.assign(logs, logs),
    index: Object.assign(index, index),
    workers: Object.assign(workers, workers),
}

export default labour