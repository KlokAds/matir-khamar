import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ReminderController::act
* @see app/Http/Controllers/ReminderController.php:97
* @route '/reminders/{reminder}/act/{action}'
*/
export const act = (args: { reminder: number | { id: number }, action: string | number } | [reminder: number | { id: number }, action: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: act.url(args, options),
    method: 'post',
})

act.definition = {
    methods: ["post"],
    url: '/reminders/{reminder}/act/{action}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ReminderController::act
* @see app/Http/Controllers/ReminderController.php:97
* @route '/reminders/{reminder}/act/{action}'
*/
act.url = (args: { reminder: number | { id: number }, action: string | number } | [reminder: number | { id: number }, action: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            reminder: args[0],
            action: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reminder: typeof args.reminder === 'object'
        ? args.reminder.id
        : args.reminder,
        action: args.action,
    }

    return act.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace('{action}', parsedArgs.action.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::act
* @see app/Http/Controllers/ReminderController.php:97
* @route '/reminders/{reminder}/act/{action}'
*/
act.post = (args: { reminder: number | { id: number }, action: string | number } | [reminder: number | { id: number }, action: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: act.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::act
* @see app/Http/Controllers/ReminderController.php:97
* @route '/reminders/{reminder}/act/{action}'
*/
const actForm = (args: { reminder: number | { id: number }, action: string | number } | [reminder: number | { id: number }, action: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: act.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::act
* @see app/Http/Controllers/ReminderController.php:97
* @route '/reminders/{reminder}/act/{action}'
*/
actForm.post = (args: { reminder: number | { id: number }, action: string | number } | [reminder: number | { id: number }, action: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: act.url(args, options),
    method: 'post',
})

act.form = actForm

/**
* @see \App\Http\Controllers\ReminderController::index
* @see app/Http/Controllers/ReminderController.php:27
* @route '/reminders'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/reminders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReminderController::index
* @see app/Http/Controllers/ReminderController.php:27
* @route '/reminders'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::index
* @see app/Http/Controllers/ReminderController.php:27
* @route '/reminders'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReminderController::index
* @see app/Http/Controllers/ReminderController.php:27
* @route '/reminders'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReminderController::index
* @see app/Http/Controllers/ReminderController.php:27
* @route '/reminders'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReminderController::index
* @see app/Http/Controllers/ReminderController.php:27
* @route '/reminders'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReminderController::index
* @see app/Http/Controllers/ReminderController.php:27
* @route '/reminders'
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
* @see \App\Http\Controllers\ReminderController::store
* @see app/Http/Controllers/ReminderController.php:66
* @route '/reminders'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/reminders',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ReminderController::store
* @see app/Http/Controllers/ReminderController.php:66
* @route '/reminders'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::store
* @see app/Http/Controllers/ReminderController.php:66
* @route '/reminders'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::store
* @see app/Http/Controllers/ReminderController.php:66
* @route '/reminders'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::store
* @see app/Http/Controllers/ReminderController.php:66
* @route '/reminders'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\ReminderController::update
* @see app/Http/Controllers/ReminderController.php:84
* @route '/reminders/{reminder}'
*/
export const update = (args: { reminder: number | { id: number } } | [reminder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/reminders/{reminder}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ReminderController::update
* @see app/Http/Controllers/ReminderController.php:84
* @route '/reminders/{reminder}'
*/
update.url = (args: { reminder: number | { id: number } } | [reminder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reminder: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { reminder: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            reminder: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reminder: typeof args.reminder === 'object'
        ? args.reminder.id
        : args.reminder,
    }

    return update.definition.url
            .replace('{reminder}', parsedArgs.reminder.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::update
* @see app/Http/Controllers/ReminderController.php:84
* @route '/reminders/{reminder}'
*/
update.put = (args: { reminder: number | { id: number } } | [reminder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ReminderController::update
* @see app/Http/Controllers/ReminderController.php:84
* @route '/reminders/{reminder}'
*/
const updateForm = (args: { reminder: number | { id: number } } | [reminder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::update
* @see app/Http/Controllers/ReminderController.php:84
* @route '/reminders/{reminder}'
*/
updateForm.put = (args: { reminder: number | { id: number } } | [reminder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const reminders = {
    act: Object.assign(act, act),
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    update: Object.assign(update, update),
}

export default reminders