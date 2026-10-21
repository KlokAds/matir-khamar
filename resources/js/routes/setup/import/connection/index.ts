import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ImportController::test
* @see app/Http/Controllers/ImportController.php:127
* @route '/setup/import/connection/test'
*/
export const test = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: test.url(options),
    method: 'post',
})

test.definition = {
    methods: ["post"],
    url: '/setup/import/connection/test',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ImportController::test
* @see app/Http/Controllers/ImportController.php:127
* @route '/setup/import/connection/test'
*/
test.url = (options?: RouteQueryOptions) => {
    return test.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::test
* @see app/Http/Controllers/ImportController.php:127
* @route '/setup/import/connection/test'
*/
test.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: test.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::test
* @see app/Http/Controllers/ImportController.php:127
* @route '/setup/import/connection/test'
*/
const testForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: test.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::test
* @see app/Http/Controllers/ImportController.php:127
* @route '/setup/import/connection/test'
*/
testForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: test.url(options),
    method: 'post',
})

test.form = testForm

/**
* @see \App\Http\Controllers\ImportController::forget
* @see app/Http/Controllers/ImportController.php:136
* @route '/setup/import/connection'
*/
export const forget = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: forget.url(options),
    method: 'delete',
})

forget.definition = {
    methods: ["delete"],
    url: '/setup/import/connection',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ImportController::forget
* @see app/Http/Controllers/ImportController.php:136
* @route '/setup/import/connection'
*/
forget.url = (options?: RouteQueryOptions) => {
    return forget.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ImportController::forget
* @see app/Http/Controllers/ImportController.php:136
* @route '/setup/import/connection'
*/
forget.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: forget.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ImportController::forget
* @see app/Http/Controllers/ImportController.php:136
* @route '/setup/import/connection'
*/
const forgetForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: forget.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ImportController::forget
* @see app/Http/Controllers/ImportController.php:136
* @route '/setup/import/connection'
*/
forgetForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: forget.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

forget.form = forgetForm

const connection = {
    test: Object.assign(test, test),
    forget: Object.assign(forget, forget),
}

export default connection