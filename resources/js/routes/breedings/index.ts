import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\HerdController::store
* @see app/Http/Controllers/HerdController.php:56
* @route '/animals/{animal}/breedings'
*/
export const store = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/animals/{animal}/breedings',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\HerdController::store
* @see app/Http/Controllers/HerdController.php:56
* @route '/animals/{animal}/breedings'
*/
store.url = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { animal: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { animal: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            animal: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        animal: typeof args.animal === 'object'
        ? args.animal.id
        : args.animal,
    }

    return store.definition.url
            .replace('{animal}', parsedArgs.animal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::store
* @see app/Http/Controllers/HerdController.php:56
* @route '/animals/{animal}/breedings'
*/
store.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::store
* @see app/Http/Controllers/HerdController.php:56
* @route '/animals/{animal}/breedings'
*/
const storeForm = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::store
* @see app/Http/Controllers/HerdController.php:56
* @route '/animals/{animal}/breedings'
*/
storeForm.post = (args: { animal: number | { id: number } } | [animal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\HerdController::update
* @see app/Http/Controllers/HerdController.php:78
* @route '/breedings/{breeding}'
*/
export const update = (args: { breeding: number | { id: number } } | [breeding: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/breedings/{breeding}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\HerdController::update
* @see app/Http/Controllers/HerdController.php:78
* @route '/breedings/{breeding}'
*/
update.url = (args: { breeding: number | { id: number } } | [breeding: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { breeding: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { breeding: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            breeding: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        breeding: typeof args.breeding === 'object'
        ? args.breeding.id
        : args.breeding,
    }

    return update.definition.url
            .replace('{breeding}', parsedArgs.breeding.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\HerdController::update
* @see app/Http/Controllers/HerdController.php:78
* @route '/breedings/{breeding}'
*/
update.put = (args: { breeding: number | { id: number } } | [breeding: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\HerdController::update
* @see app/Http/Controllers/HerdController.php:78
* @route '/breedings/{breeding}'
*/
const updateForm = (args: { breeding: number | { id: number } } | [breeding: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\HerdController::update
* @see app/Http/Controllers/HerdController.php:78
* @route '/breedings/{breeding}'
*/
updateForm.put = (args: { breeding: number | { id: number } } | [breeding: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const breedings = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
}

export default breedings