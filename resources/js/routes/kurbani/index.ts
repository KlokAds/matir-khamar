import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\KurbaniController::index
* @see app/Http/Controllers/KurbaniController.php:20
* @route '/kurbani'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/kurbani',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\KurbaniController::index
* @see app/Http/Controllers/KurbaniController.php:20
* @route '/kurbani'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\KurbaniController::index
* @see app/Http/Controllers/KurbaniController.php:20
* @route '/kurbani'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\KurbaniController::index
* @see app/Http/Controllers/KurbaniController.php:20
* @route '/kurbani'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\KurbaniController::index
* @see app/Http/Controllers/KurbaniController.php:20
* @route '/kurbani'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\KurbaniController::index
* @see app/Http/Controllers/KurbaniController.php:20
* @route '/kurbani'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\KurbaniController::index
* @see app/Http/Controllers/KurbaniController.php:20
* @route '/kurbani'
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
* @see \App\Http\Controllers\KurbaniController::eid
* @see app/Http/Controllers/KurbaniController.php:38
* @route '/kurbani/eid'
*/
export const eid = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: eid.url(options),
    method: 'put',
})

eid.definition = {
    methods: ["put"],
    url: '/kurbani/eid',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\KurbaniController::eid
* @see app/Http/Controllers/KurbaniController.php:38
* @route '/kurbani/eid'
*/
eid.url = (options?: RouteQueryOptions) => {
    return eid.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\KurbaniController::eid
* @see app/Http/Controllers/KurbaniController.php:38
* @route '/kurbani/eid'
*/
eid.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: eid.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\KurbaniController::eid
* @see app/Http/Controllers/KurbaniController.php:38
* @route '/kurbani/eid'
*/
const eidForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: eid.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\KurbaniController::eid
* @see app/Http/Controllers/KurbaniController.php:38
* @route '/kurbani/eid'
*/
eidForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: eid.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

eid.form = eidForm

const kurbani = {
    index: Object.assign(index, index),
    eid: Object.assign(eid, eid),
}

export default kurbani