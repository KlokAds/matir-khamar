import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\KurbaniController::updateEid
* @see app/Http/Controllers/KurbaniController.php:38
* @route '/kurbani/eid'
*/
export const updateEid = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateEid.url(options),
    method: 'put',
})

updateEid.definition = {
    methods: ["put"],
    url: '/kurbani/eid',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\KurbaniController::updateEid
* @see app/Http/Controllers/KurbaniController.php:38
* @route '/kurbani/eid'
*/
updateEid.url = (options?: RouteQueryOptions) => {
    return updateEid.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\KurbaniController::updateEid
* @see app/Http/Controllers/KurbaniController.php:38
* @route '/kurbani/eid'
*/
updateEid.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateEid.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\KurbaniController::updateEid
* @see app/Http/Controllers/KurbaniController.php:38
* @route '/kurbani/eid'
*/
const updateEidForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateEid.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\KurbaniController::updateEid
* @see app/Http/Controllers/KurbaniController.php:38
* @route '/kurbani/eid'
*/
updateEidForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateEid.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateEid.form = updateEidForm

const KurbaniController = { index, updateEid }

export default KurbaniController