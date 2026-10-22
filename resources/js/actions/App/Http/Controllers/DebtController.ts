import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DebtController::index
* @see app/Http/Controllers/DebtController.php:20
* @route '/debts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/debts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DebtController::index
* @see app/Http/Controllers/DebtController.php:20
* @route '/debts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DebtController::index
* @see app/Http/Controllers/DebtController.php:20
* @route '/debts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DebtController::index
* @see app/Http/Controllers/DebtController.php:20
* @route '/debts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DebtController::index
* @see app/Http/Controllers/DebtController.php:20
* @route '/debts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DebtController::index
* @see app/Http/Controllers/DebtController.php:20
* @route '/debts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DebtController::index
* @see app/Http/Controllers/DebtController.php:20
* @route '/debts'
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
* @see \App\Http\Controllers\DebtController::store
* @see app/Http/Controllers/DebtController.php:36
* @route '/debts'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/debts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DebtController::store
* @see app/Http/Controllers/DebtController.php:36
* @route '/debts'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DebtController::store
* @see app/Http/Controllers/DebtController.php:36
* @route '/debts'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DebtController::store
* @see app/Http/Controllers/DebtController.php:36
* @route '/debts'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DebtController::store
* @see app/Http/Controllers/DebtController.php:36
* @route '/debts'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\DebtController::move
* @see app/Http/Controllers/DebtController.php:60
* @route '/debts/{debt}/move'
*/
export const move = (args: { debt: number | { id: number } } | [debt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: move.url(args, options),
    method: 'post',
})

move.definition = {
    methods: ["post"],
    url: '/debts/{debt}/move',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DebtController::move
* @see app/Http/Controllers/DebtController.php:60
* @route '/debts/{debt}/move'
*/
move.url = (args: { debt: number | { id: number } } | [debt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { debt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { debt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            debt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        debt: typeof args.debt === 'object'
        ? args.debt.id
        : args.debt,
    }

    return move.definition.url
            .replace('{debt}', parsedArgs.debt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DebtController::move
* @see app/Http/Controllers/DebtController.php:60
* @route '/debts/{debt}/move'
*/
move.post = (args: { debt: number | { id: number } } | [debt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: move.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DebtController::move
* @see app/Http/Controllers/DebtController.php:60
* @route '/debts/{debt}/move'
*/
const moveForm = (args: { debt: number | { id: number } } | [debt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: move.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DebtController::move
* @see app/Http/Controllers/DebtController.php:60
* @route '/debts/{debt}/move'
*/
moveForm.post = (args: { debt: number | { id: number } } | [debt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: move.url(args, options),
    method: 'post',
})

move.form = moveForm

/**
* @see \App\Http\Controllers\DebtController::plan
* @see app/Http/Controllers/DebtController.php:76
* @route '/debts/{debt}/plan'
*/
export const plan = (args: { debt: number | { id: number } } | [debt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: plan.url(args, options),
    method: 'put',
})

plan.definition = {
    methods: ["put"],
    url: '/debts/{debt}/plan',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\DebtController::plan
* @see app/Http/Controllers/DebtController.php:76
* @route '/debts/{debt}/plan'
*/
plan.url = (args: { debt: number | { id: number } } | [debt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { debt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { debt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            debt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        debt: typeof args.debt === 'object'
        ? args.debt.id
        : args.debt,
    }

    return plan.definition.url
            .replace('{debt}', parsedArgs.debt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DebtController::plan
* @see app/Http/Controllers/DebtController.php:76
* @route '/debts/{debt}/plan'
*/
plan.put = (args: { debt: number | { id: number } } | [debt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: plan.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DebtController::plan
* @see app/Http/Controllers/DebtController.php:76
* @route '/debts/{debt}/plan'
*/
const planForm = (args: { debt: number | { id: number } } | [debt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: plan.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DebtController::plan
* @see app/Http/Controllers/DebtController.php:76
* @route '/debts/{debt}/plan'
*/
planForm.put = (args: { debt: number | { id: number } } | [debt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: plan.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

plan.form = planForm

const DebtController = { index, store, move, plan }

export default DebtController