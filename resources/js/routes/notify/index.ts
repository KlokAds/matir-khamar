import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ReminderController::prefs
* @see app/Http/Controllers/ReminderController.php:179
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
* @see app/Http/Controllers/ReminderController.php:179
* @route '/notify-prefs'
*/
prefs.url = (options?: RouteQueryOptions) => {
    return prefs.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReminderController::prefs
* @see app/Http/Controllers/ReminderController.php:179
* @route '/notify-prefs'
*/
prefs.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: prefs.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ReminderController::prefs
* @see app/Http/Controllers/ReminderController.php:179
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
* @see app/Http/Controllers/ReminderController.php:179
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

const notify = {
    prefs: Object.assign(prefs, prefs),
}

export default notify