<?php

namespace AlertsUA;

/**
 * Base class for all API errors
 */
class ApiError extends \Exception {}

/**
 * Error thrown when the API token is invalid or missing
 */
class UnauthorizedError extends ApiError {}

/**
 * Error thrown when too many requests are made to the API
 */
class RateLimitError extends ApiError {}

/**
 * Error thrown when the server encounters an internal error
 */
class InternalServerError extends ApiError {}

/**
 * Error thrown when access to a resource is forbidden
 */
class ForbiddenError extends ApiError {}

/**
 * Error thrown when an invalid parameter is provided
 */
class InvalidParameterException extends \Exception {}

/**
 * Error thrown when a requested resource is not found
 */
class NotFoundError extends ApiError {}

/**
 * Error thrown when the request is malformed
 */
class BadRequestError extends ApiError {}
