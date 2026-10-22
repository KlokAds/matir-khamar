import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ReminderController::act
* @see app/Http/Controllers/ReminderController.php:108
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
* @see app/Http/Controllers/ReminderController.php:108
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
* @see app/Http/Controllers/ReminderController.php:108
* @route '/reminders/{reminder}/act/{action}'
*/
act.post = (args: { reminder: number | { id: number }, action: string | number } | [reminder: number | { id: number }, action: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: act.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::act
* @see app/Http/Controllers/ReminderController.php:108
* @route '/reminders/{reminder}/act/{action}'
*/
const actForm = (args: { reminder: number | { id: number }, action: string | number } | [reminder: number | { id: number }, action: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: act.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::act
* @see app/Http/Controllers/ReminderController.php:108
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
* @see app/Http/Controllers/ReminderController.php:76
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
* @see app/Http/Controllers/ReminderController.php:76
* @route '/reminders'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::store
* @see app/Http/Controllers/ReminderController.php:76
* @route '/reminders'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::store
* @see app/Http/Controllers/ReminderController.php:76
* @route '/reminders'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::store
* @see app/Http/Controllers/ReminderController.php:76
* @route '/reminders'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\ReminderController::update
* @see app/Http/Controllers/ReminderController.php:95
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
* @see app/Http/Controllers/ReminderController.php:95
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
* @see app/Http/Controllers/ReminderController.php:95
* @route '/reminders/{reminder}'
*/
update.put = (args: { reminder: number | { id: number } } | [reminder: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ReminderController::update
* @see app/Http/Controllers/ReminderController.php:95
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
* @see app/Http/Controllers/ReminderController.php:95
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

/**
* @see \App\Http\Controllers\ReminderController::subscribe
* @see app/Http/Controllers/ReminderController.php:118
* @route '/push/subscribe'
*/
export const subscribe = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: subscribe.url(options),
    method: 'post',
})

subscribe.definition = {
    methods: ["post"],
    url: '/push/subscribe',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ReminderController::subscribe
* @see app/Http/Controllers/ReminderController.php:118
* @route '/push/subscribe'
*/
subscribe.url = (options?: RouteQueryOptions) => {
    return subscribe.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::subscribe
* @see app/Http/Controllers/ReminderController.php:118
* @route '/push/subscribe'
*/
subscribe.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: subscribe.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::subscribe
* @see app/Http/Controllers/ReminderController.php:118
* @route '/push/subscribe'
*/
const subscribeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: subscribe.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::subscribe
* @see app/Http/Controllers/ReminderController.php:118
* @route '/push/subscribe'
*/
subscribeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: subscribe.url(options),
    method: 'post',
})

subscribe.form = subscribeForm

/**
* @see \App\Http\Controllers\ReminderController::unsubscribe
* @see app/Http/Controllers/ReminderController.php:140
* @route '/push/unsubscribe'
*/
export const unsubscribe = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unsubscribe.url(options),
    method: 'post',
})

unsubscribe.definition = {
    methods: ["post"],
    url: '/push/unsubscribe',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ReminderController::unsubscribe
* @see app/Http/Controllers/ReminderController.php:140
* @route '/push/unsubscribe'
*/
unsubscribe.url = (options?: RouteQueryOptions) => {
    return unsubscribe.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::unsubscribe
* @see app/Http/Controllers/ReminderController.php:140
* @route '/push/unsubscribe'
*/
unsubscribe.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unsubscribe.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::unsubscribe
* @see app/Http/Controllers/ReminderController.php:140
* @route '/push/unsubscribe'
*/
const unsubscribeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unsubscribe.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::unsubscribe
* @see app/Http/Controllers/ReminderController.php:140
* @route '/push/unsubscribe'
*/
unsubscribeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unsubscribe.url(options),
    method: 'post',
})

unsubscribe.form = unsubscribeForm

/**
* @see \App\Http\Controllers\ReminderController::test
* @see app/Http/Controllers/ReminderController.php:148
* @route '/push/test'
*/
export const test = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: test.url(options),
    method: 'post',
})

test.definition = {
    methods: ["post"],
    url: '/push/test',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ReminderController::test
* @see app/Http/Controllers/ReminderController.php:148
* @route '/push/test'
*/
test.url = (options?: RouteQueryOptions) => {
    return test.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::test
* @see app/Http/Controllers/ReminderController.php:148
* @route '/push/test'
*/
test.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: test.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::test
* @see app/Http/Controllers/ReminderController.php:148
* @route '/push/test'
*/
const testForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: test.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::test
* @see app/Http/Controllers/ReminderController.php:148
* @route '/push/test'
*/
testForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: test.url(options),
    method: 'post',
})

test.form = testForm

/**
* @see \App\Http\Controllers\ReminderController::received
* @see app/Http/Controllers/ReminderController.php:189
* @route '/push/received'
*/
export const received = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: received.url(options),
    method: 'post',
})

received.definition = {
    methods: ["post"],
    url: '/push/received',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ReminderController::received
* @see app/Http/Controllers/ReminderController.php:189
* @route '/push/received'
*/
received.url = (options?: RouteQueryOptions) => {
    return received.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::received
* @see app/Http/Controllers/ReminderController.php:189
* @route '/push/received'
*/
received.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: received.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::received
* @see app/Http/Controllers/ReminderController.php:189
* @route '/push/received'
*/
const receivedForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: received.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::received
* @see app/Http/Controllers/ReminderController.php:189
* @route '/push/received'
*/
receivedForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: received.url(options),
    method: 'post',
})

received.form = receivedForm

/**
* @see \App\Http\Controllers\ReminderController::app
* @see app/Http/Controllers/ReminderController.php:168
* @route '/push/app'
*/
export const app = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: app.url(options),
    method: 'post',
})

app.definition = {
    methods: ["post"],
    url: '/push/app',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ReminderController::app
* @see app/Http/Controllers/ReminderController.php:168
* @route '/push/app'
*/
app.url = (options?: RouteQueryOptions) => {
    return app.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::app
* @see app/Http/Controllers/ReminderController.php:168
* @route '/push/app'
*/
app.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: app.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::app
* @see app/Http/Controllers/ReminderController.php:168
* @route '/push/app'
*/
const appForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: app.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::app
* @see app/Http/Controllers/ReminderController.php:168
* @route '/push/app'
*/
appForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: app.url(options),
    method: 'post',
})

app.form = appForm

/**
* @see \App\Http\Controllers\ReminderController::prefs
* @see app/Http/Controllers/ReminderController.php:200
* @route '/notify-prefs'
*/
export const prefs = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: prefs.url(options),
    method: 'put',
})

prefs.definition = {
    methods: ["put"],
    url: '/notify-prefs',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ReminderController::prefs
* @see app/Http/Controllers/ReminderController.php:200
* @route '/notify-prefs'
*/
prefs.url = (options?: RouteQueryOptions) => {
    return prefs.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::prefs
* @see app/Http/Controllers/ReminderController.php:200
* @route '/notify-prefs'
*/
prefs.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: prefs.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ReminderController::prefs
* @see app/Http/Controllers/ReminderController.php:200
* @route '/notify-prefs'
*/
const prefsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: prefs.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::prefs
* @see app/Http/Controllers/ReminderController.php:200
* @route '/notify-prefs'
*/
prefsForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: prefs.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

prefs.form = prefsForm

const ReminderController = { act, index, store, update, subscribe, unsubscribe, test, received, app, prefs }

export default ReminderController