import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\UdyogController::index
* @see app/Http/Controllers/UdyogController.php:21
* @route '/udyog'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/udyog',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UdyogController::index
* @see app/Http/Controllers/UdyogController.php:21
* @route '/udyog'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UdyogController::index
* @see app/Http/Controllers/UdyogController.php:21
* @route '/udyog'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UdyogController::index
* @see app/Http/Controllers/UdyogController.php:21
* @route '/udyog'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UdyogController::index
* @see app/Http/Controllers/UdyogController.php:21
* @route '/udyog'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UdyogController::index
* @see app/Http/Controllers/UdyogController.php:21
* @route '/udyog'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UdyogController::index
* @see app/Http/Controllers/UdyogController.php:21
* @route '/udyog'
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
* @see \App\Http\Controllers\UdyogController::update
* @see app/Http/Controllers/UdyogController.php:37
* @route '/udyog'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/udyog',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\UdyogController::update
* @see app/Http/Controllers/UdyogController.php:37
* @route '/udyog'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UdyogController::update
* @see app/Http/Controllers/UdyogController.php:37
* @route '/udyog'
*/
update.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\UdyogController::update
* @see app/Http/Controllers/UdyogController.php:37
* @route '/udyog'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UdyogController::update
* @see app/Http/Controllers/UdyogController.php:37
* @route '/udyog'
*/
updateForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\UdyogController::approve
* @see app/Http/Controllers/UdyogController.php:63
* @route '/udyog/approve'
*/
export const approve = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/udyog/approve',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\UdyogController::approve
* @see app/Http/Controllers/UdyogController.php:63
* @route '/udyog/approve'
*/
approve.url = (options?: RouteQueryOptions) => {
    return approve.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UdyogController::approve
* @see app/Http/Controllers/UdyogController.php:63
* @route '/udyog/approve'
*/
approve.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UdyogController::approve
* @see app/Http/Controllers/UdyogController.php:63
* @route '/udyog/approve'
*/
const approveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UdyogController::approve
* @see app/Http/Controllers/UdyogController.php:63
* @route '/udyog/approve'
*/
approveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(options),
    method: 'post',
})

approve.form = approveForm

const UdyogController = { index, update, approve }

export default UdyogController