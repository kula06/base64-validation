<?php
declare(strict_types = 1);

namespace Crazybooot\Base64Validation\Validators;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Concerns\ValidatesAttributes;
use Symfony\Component\HttpFoundation\File\File;
use Illuminate\Http\UploadedFile;
use function base64_decode;
use function explode;
use function file_put_contents;
use function stream_get_meta_data;
use function strpos;
use function tmpfile;
use const true;

/**
 * Class Base64Validator
 *
 * @package Crazybooot\Base64Validation\Validators
 */
class Base64Validator
{
    use ValidatesAttributes;

    private $tmpFileDescriptor;

    /**
     * @param string    $attribute
     * @param mixed     $value
     * @param array     $parameters
     * @param Validator $validator
     *
     * @return bool
     */
    public function validateBase64Max(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        return !empty($value)
            ? $validator->validateMax($attribute, $this->convertToFile($value), $parameters)
            : true;
    }

    /**
     * @param string    $attribute
     * @param mixed     $value
     * @param array     $parameters
     * @param Validator $validator
     *
     * @return bool
     */
    public function validateBase64Min(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        return !empty($value)
            ? $validator->validateMin($attribute, $this->convertToFile($value), $parameters)
            : true;
    }

    /**
     * @param string    $attribute
     * @param mixed     $value
     * @param array     $parameters
     * @param Validator $validator
     *
     * @return bool
     */
    public function validateBase64Dimensions(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        return !empty($value)
            ? $this->validateDimensions($attribute, $this->convertToFile($value), $parameters)
            : true;
    }

    /**
     * @param string    $attribute
     * @param mixed     $value
     * @param array     $parameters
     * @param Validator $validator
     *
     * @return bool
     */
    public function validateBase64File(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        return !empty($value)
            ? $validator->validateFile($attribute, $this->convertToFile($value))
            : true;
    }

    /**
     * @param string    $attribute
     * @param mixed     $value
     * @param array     $parameters
     * @param Validator $validator
     *
     * @return bool
     */
    public function validateBase64Image(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        return !empty($value)
            ? $validator->validateImage($attribute, $this->convertToFile($value))
            : true;
    }

    /**
     * @param string    $attribute
     * @param mixed     $value
     * @param array     $parameters
     * @param Validator $validator
     *
     * @return bool
     */
    public function validateBase64Mimetypes(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        return !empty($value)
            ? $validator->validateMimetypes($attribute, $this->convertToFile($value), $parameters)
            : true;
    }

    /**
     * @param string    $attribute
     * @param mixed     $value
     * @param array     $parameters
     * @param Validator $validator
     *
     * @return bool
     */
    public function validateBase64Mimes(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        return !empty($value)
            ? $validator->validateMimes($attribute, $this->convertToFile($value), $parameters)
            : true;
    }

    /**
     * @param string    $attribute
     * @param mixed     $value
     * @param array     $parameters
     * @param Validator $validator
     *
     * @return bool
     */
    public function validateBase64Between(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        return !empty($value)
            ? $validator->validateBetween($attribute, $this->convertToFile($value), $parameters)
            : true;
    }

    /**
     * @param string    $attribute
     * @param mixed     $value
     * @param array     $parameters
     * @param Validator $validator
     *
     * @return bool
     */
    public function validateBase64Size(string $attribute, $value, array $parameters, Validator $validator): bool
    {
        return !empty($value)
            ? $validator->validateSize($attribute, $this->convertToFile($value), $parameters)
            : true;
    }

    /**
     * @param mixed $value
     *
     * @return File|UploadedFile
     */
    protected function convertToFile($value)
    {
        if ($value instanceof UploadedFile)
            return $value;

        if (strpos($value, ';base64') !== false) {
            [, $value] = explode(';', $value);
            [, $value] = explode(',', $value);
        }

        $binaryData = base64_decode($value);
        $tmpFile = tmpfile();
        $this->tmpFileDescriptor = $tmpFile;
        $tmpFilePath = stream_get_meta_data($tmpFile)['uri'];

        file_put_contents($tmpFilePath, $binaryData);

        return new File($tmpFilePath);
    }

    /**
     * @param $attribute
     * @param $value
     * @param $parameters
     *
     * @return bool
     */
    public function validateDimensions($attribute, $value, $parameters)
    {
        if ($this->isValidFileInstance($value) && $value->getMimeType() === 'image/svg+xml') {
            return true;
        }

        if (! $this->isValidFileInstance($value) || ! $sizeDetails = @getimagesize($value->getRealPath())) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'dimensions');

        [$width, $height] = $sizeDetails;

        $parameters = $this->parseNamedParameters($parameters);

        if ($this->failsBasicDimensionChecks($parameters, $width, $height) ||
            $this->failsRatioCheck($parameters, $width, $height)) {
            return false;
        }

        return true;
    }
}
