import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
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

const reports = {
    index: Object.assign(index, index),
    budget: Object.assign(budget, budget),
}

export default reports