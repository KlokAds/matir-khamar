import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\DiaryController::store
* @see app/Http/Controllers/DiaryController.php:66
* @route '/diary/notes'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/diary/notes',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DiaryController::store
* @see app/Http/Controllers/DiaryController.php:66
* @route '/diary/notes'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::store
* @see app/Http/Controllers/DiaryController.php:66
* @route '/diary/notes'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::store
* @see app/Http/Controllers/DiaryController.php:66
* @route '/diary/notes'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::store
* @see app/Http/Controllers/DiaryController.php:66
* @route '/diary/notes'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\DiaryController::update
* @see app/Http/Controllers/DiaryController.php:105
* @route '/diary/notes/{note}'
*/
export const update = (args: { note: number | { id: number } } | [note: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/diary/notes/{note}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\DiaryController::update
* @see app/Http/Controllers/DiaryController.php:105
* @route '/diary/notes/{note}'
*/
update.url = (args: { note: number | { id: number } } | [note: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { note: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { note: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            note: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        note: typeof args.note === 'object'
        ? args.note.id
        : args.note,
    }

    return update.definition.url
            .replace('{note}', parsedArgs.note.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::update
* @see app/Http/Controllers/DiaryController.php:105
* @route '/diary/notes/{note}'
*/
update.put = (args: { note: number | { id: number } } | [note: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DiaryController::update
* @see app/Http/Controllers/DiaryController.php:105
* @route '/diary/notes/{note}'
*/
const updateForm = (args: { note: number | { id: number } } | [note: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::update
* @see app/Http/Controllers/DiaryController.php:105
* @route '/diary/notes/{note}'
*/
updateForm.put = (args: { note: number | { id: number } } | [note: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const notes = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
}

export default notes