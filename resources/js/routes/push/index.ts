import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ReminderController::subscribe
* @see app/Http/Controllers/ReminderController.php:156
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
* @see app/Http/Controllers/ReminderController.php:156
* @route '/push/subscribe'
*/
subscribe.url = (options?: RouteQueryOptions) => {
    return subscribe.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::subscribe
* @see app/Http/Controllers/ReminderController.php:156
* @route '/push/subscribe'
*/
subscribe.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: subscribe.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::subscribe
* @see app/Http/Controllers/ReminderController.php:156
* @route '/push/subscribe'
*/
const subscribeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: subscribe.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::subscribe
* @see app/Http/Controllers/ReminderController.php:156
* @route '/push/subscribe'
*/
subscribeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: subscribe.url(options),
    method: 'post',
})

subscribe.form = subscribeForm

/**
* @see \App\Http\Controllers\ReminderController::unsubscribe
* @see app/Http/Controllers/ReminderController.php:178
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
* @see app/Http/Controllers/ReminderController.php:178
* @route '/push/unsubscribe'
*/
unsubscribe.url = (options?: RouteQueryOptions) => {
    return unsubscribe.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::unsubscribe
* @see app/Http/Controllers/ReminderController.php:178
* @route '/push/unsubscribe'
*/
unsubscribe.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unsubscribe.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::unsubscribe
* @see app/Http/Controllers/ReminderController.php:178
* @route '/push/unsubscribe'
*/
const unsubscribeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unsubscribe.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::unsubscribe
* @see app/Http/Controllers/ReminderController.php:178
* @route '/push/unsubscribe'
*/
unsubscribeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unsubscribe.url(options),
    method: 'post',
})

unsubscribe.form = unsubscribeForm

/**
* @see \App\Http\Controllers\ReminderController::test
* @see app/Http/Controllers/ReminderController.php:186
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
* @see app/Http/Controllers/ReminderController.php:186
* @route '/push/test'
*/
test.url = (options?: RouteQueryOptions) => {
    return test.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::test
* @see app/Http/Controllers/ReminderController.php:186
* @route '/push/test'
*/
test.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: test.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::test
* @see app/Http/Controllers/ReminderController.php:186
* @route '/push/test'
*/
const testForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: test.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::test
* @see app/Http/Controllers/ReminderController.php:186
* @route '/push/test'
*/
testForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: test.url(options),
    method: 'post',
})

test.form = testForm

/**
* @see \App\Http\Controllers\ReminderController::received
* @see app/Http/Controllers/ReminderController.php:227
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
* @see app/Http/Controllers/ReminderController.php:227
* @route '/push/received'
*/
received.url = (options?: RouteQueryOptions) => {
    return received.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::received
* @see app/Http/Controllers/ReminderController.php:227
* @route '/push/received'
*/
received.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: received.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::received
* @see app/Http/Controllers/ReminderController.php:227
* @route '/push/received'
*/
const receivedForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: received.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::received
* @see app/Http/Controllers/ReminderController.php:227
* @route '/push/received'
*/
receivedForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: received.url(options),
    method: 'post',
})

received.form = receivedForm

/**
* @see \App\Http\Controllers\ReminderController::app
* @see app/Http/Controllers/ReminderController.php:206
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
* @see app/Http/Controllers/ReminderController.php:206
* @route '/push/app'
*/
app.url = (options?: RouteQueryOptions) => {
    return app.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::app
* @see app/Http/Controllers/ReminderController.php:206
* @route '/push/app'
*/
app.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: app.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::app
* @see app/Http/Controllers/ReminderController.php:206
* @route '/push/app'
*/
const appForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: app.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReminderController::app
* @see app/Http/Controllers/ReminderController.php:206
* @route '/push/app'
*/
appForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: app.url(options),
    method: 'post',
})

app.form = appForm

const push = {
    subscribe: Object.assign(subscribe, subscribe),
    unsubscribe: Object.assign(unsubscribe, unsubscribe),
    test: Object.assign(test, test),
    received: Object.assign(received, received),
    app: Object.assign(app, app),
}

export default push