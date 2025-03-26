<?php

namespace AlertsUA;

class ApiError extends \Exception {}
class UnauthorizedError extends ApiError {}
class RateLimitError extends ApiError {}
class InternalServerError extends ApiError {}
class ForbiddenError extends ApiError {}
class InvalidParameterException extends \Exception {}
