import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\DiaryController::store
* @see app/Http/Controllers/DiaryController.php:119
* @route '/diary/goals'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/diary/goals',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DiaryController::store
* @see app/Http/Controllers/DiaryController.php:119
* @route '/diary/goals'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::store
* @see app/Http/Controllers/DiaryController.php:119
* @route '/diary/goals'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::store
* @see app/Http/Controllers/DiaryController.php:119
* @route '/diary/goals'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DiaryController::store
* @see app/Http/Controllers/DiaryController.php:119
* @route '/diary/goals'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\DiaryController::update
* @see app/Http/Controllers/DiaryController.php:129
* @route '/diary/goals/{goal}'
*/
export const update = (args: { goal: number | { id: number } } | [goal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/diary/goals/{goal}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\DiaryController::update
* @see app/Http/Controllers/DiaryController.php:129
* @route '/diary/goals/{goal}'
*/
update.url = (args: { goal: number | { id: number } } | [goal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { goal: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { goal: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            goal: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        goal: typeof args.goal === 'object'
        ? args.goal.id
        : args.goal,
    }

    return update.definition.url
            .replace('{goal}', parsedArgs.goal.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DiaryController::update
* @see app/Http/Controllers/DiaryController.php:129
* @route '/diary/goals/{goal}'
*/
update.put = (args: { goal: number | { id: number } } | [goal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DiaryController::update
* @see app/Http/Controllers/DiaryController.php:129
* @route '/diary/goals/{goal}'
*/
const updateForm = (args: { goal: number | { id: number } } | [goal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see app/Http/Controllers/DiaryController.php:129
* @route '/diary/goals/{goal}'
*/
updateForm.put = (args: { goal: number | { id: number } } | [goal: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const goals = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
}

export default goals