import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\LandController::bigha
* @see app/Http/Controllers/LandController.php:144
* @route '/setup/land/bigha'
*/
export const bigha = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: bigha.url(options),
    method: 'put',
})

bigha.definition = {
    methods: ["put"],
    url: '/setup/land/bigha',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\LandController::bigha
* @see app/Http/Controllers/LandController.php:144
* @route '/setup/land/bigha'
*/
bigha.url = (options?: RouteQueryOptions) => {
    return bigha.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\LandController::bigha
* @see app/Http/Controllers/LandController.php:144
* @route '/setup/land/bigha'
*/
bigha.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: bigha.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\LandController::bigha
* @see app/Http/Controllers/LandController.php:144
* @route '/setup/land/bigha'
*/
const bighaForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bigha.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\LandController::bigha
* @see app/Http/Controllers/LandController.php:144
* @route '/setup/land/bigha'
*/
bighaForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bigha.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

bigha.form = bighaForm

const land = {
    bigha: Object.assign(bigha, bigha),
}

export default land