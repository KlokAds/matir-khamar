import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:17
* @route '/reports'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/reports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:17
* @route '/reports'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:17
* @route '/reports'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:17
* @route '/reports'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:17
* @route '/reports'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:17
* @route '/reports'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::index
* @see app/Http/Controllers/ReportController.php:17
* @route '/reports'
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
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:40
* @route '/reports/budget'
*/
export const budget = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: budget.url(options),
    method: 'get',
})

budget.definition = {
    methods: ["get","head"],
    url: '/reports/budget',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:40
* @route '/reports/budget'
*/
budget.url = (options?: RouteQueryOptions) => {
    return budget.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:40
* @route '/reports/budget'
*/
budget.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: budget.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:40
* @route '/reports/budget'
*/
budget.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: budget.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:40
* @route '/reports/budget'
*/
const budgetForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: budget.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:40
* @route '/reports/budget'
*/
budgetForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: budget.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ReportController::budget
* @see app/Http/Controllers/ReportController.php:40
* @route '/reports/budget'
*/
budgetForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: budget.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

budget.form = budgetForm

/**
* @see \App\Http\Controllers\ReportController::updateBudgetHead
* @see app/Http/Controllers/ReportController.php:60
* @route '/setup/budget/{head}'
*/
export const updateBudgetHead = (args: { head: number | { id: number } } | [head: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateBudgetHead.url(args, options),
    method: 'put',
})

updateBudgetHead.definition = {
    methods: ["put"],
    url: '/setup/budget/{head}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ReportController::updateBudgetHead
* @see app/Http/Controllers/ReportController.php:60
* @route '/setup/budget/{head}'
*/
updateBudgetHead.url = (args: { head: number | { id: number } } | [head: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { head: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { head: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            head: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        head: typeof args.head === 'object'
        ? args.head.id
        : args.head,
    }

    return updateBudgetHead.definition.url
            .replace('{head}', parsedArgs.head.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::updateBudgetHead
* @see app/Http/Controllers/ReportController.php:60
* @route '/setup/budget/{head}'
*/
updateBudgetHead.put = (args: { head: number | { id: number } } | [head: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateBudgetHead.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ReportController::updateBudgetHead
* @see app/Http/Controllers/ReportController.php:60
* @route '/setup/budget/{head}'
*/
const updateBudgetHeadForm = (args: { head: number | { id: number } } | [head: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateBudgetHead.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ReportController::updateBudgetHead
* @see app/Http/Controllers/ReportController.php:60
* @route '/setup/budget/{head}'
*/
updateBudgetHeadForm.put = (args: { head: number | { id: number } } | [head: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateBudgetHead.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateBudgetHead.form = updateBudgetHeadForm

const ReportController = { index, budget, updateBudgetHead }

export default ReportController