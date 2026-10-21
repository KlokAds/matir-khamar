import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\SetupController::store
* @see app/Http/Controllers/SetupController.php:183
* @route '/setup/parties'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/setup/parties',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SetupController::store
* @see app/Http/Controllers/SetupController.php:183
* @route '/setup/parties'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::store
* @see app/Http/Controllers/SetupController.php:183
* @route '/setup/parties'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::store
* @see app/Http/Controllers/SetupController.php:183
* @route '/setup/parties'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::store
* @see app/Http/Controllers/SetupController.php:183
* @route '/setup/parties'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\SetupController::update
* @see app/Http/Controllers/SetupController.php:200
* @route '/setup/parties/{party}'
*/
export const update = (args: { party: string | number | { id: string | number } } | [party: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/setup/parties/{party}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SetupController::update
* @see app/Http/Controllers/SetupController.php:200
* @route '/setup/parties/{party}'
*/
update.url = (args: { party: string | number | { id: string | number } } | [party: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { party: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { party: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            party: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        party: typeof args.party === 'object'
        ? args.party.id
        : args.party,
    }

    return update.definition.url
            .replace('{party}', parsedArgs.party.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SetupController::update
* @see app/Http/Controllers/SetupController.php:200
* @route '/setup/parties/{party}'
*/
update.put = (args: { party: string | number | { id: string | number } } | [party: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SetupController::update
* @see app/Http/Controllers/SetupController.php:200
* @route '/setup/parties/{party}'
*/
const updateForm = (args: { party: string | number | { id: string | number } } | [party: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SetupController::update
* @see app/Http/Controllers/SetupController.php:200
* @route '/setup/parties/{party}'
*/
updateForm.put = (args: { party: string | number | { id: string | number } } | [party: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const parties = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
}

export default parties