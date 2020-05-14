# HTTP Header Plugin

This plugin allows you to set HTTP Headers in your template.

## Usage

### {exp:http_header}

#### Example Usage

This is a single tag that will set a 410 "Gone" status on the request.

```
{exp:http_header status="410"}
```

#### Parameters

- `access_control_allow_credentials=` - Sets the `Access-Control-Allow-Credentials` HTTP Header.
- `access_control_allow_headers=` - Sets the `Access-Control-Allow-Headers` HTTP Header.
- `access_control_allow_methods=` - Sets the `Access-Control-Allow-Methods` HTTP Header.
- `access_control_allow_origin=` - Sets the `Access-Control-Allow-Origin` HTTP Header.
- `access_control_expose_headers=` - Sets the `Access-Control-Expose-Headers` HTTP Header.
- `access_control_max_age=` - Sets the `Access-Control-Max-Age` HTTP Header.
- `alt_svc=` - Sets the `Alt-Svc` HTTP Header.
- `cache_control=` - Sets the `Cache-Control` HTTP Header.
- `charset=` - Sets the charset to use with the `content_type` paramter.
- `content_disposition=` - Sets the `Content-Disposition` HTTP Header. You can manually write out the full header value (i.e. `attachment; filename="example.txt"`), or just use a value of "attachmenet" and use the `filename=` parameter.
- `content_encoding=` - Sets the `Content-Encoding` HTTP Header.
- `content_language=` - Sets the `Content-Language` HTTP Header.
- `content_length=` - Sets the `Content-Length` HTTP Header.
- `content_location=` - Sets the `Content-Location` HTTP Header.
- `content_md5=` - Sets the `Content-MD5` HTTP Header.
- `content_range=` - Sets the `Content-Range` HTTP Header.
- `content_type=` - Sets the `Content-Type` HTTP Header. You can manually write out the full header value (i.e. `text/html; charset=UTF-8`) or you can simply specify the type and use the `charset=` parameter.
- `etag=` - Sets the `ETag` HTTP Header.
- `expires=` - Sets the `Expires` HTTP Header. You can use relative date such as "+1 day";
- `filename=` - When `content_disposition` is set to `attachment` this sets the filename.
- `last_modified=` - Sets the `Last-Modified` HTTP Header. You can use relative date such as "+1 day";
- `link=` - Sets the `Link` HTTP Header.
- `location=` - Sets the `Location` HTTP Header.
- `pragma=` - Sets the `Pragma` HTTP Header.
- `refresh=` - Sets the `Refresh` HTTP Header. You can manually write out the full header value (i.e. `5; url=http://example.com/`), or just set the refresh value in seconds and use the `url=` parameter.
- `retry_after=` - Sets the `Retry-After` HTTP Header. You can eitehr specify a number of seconds or use relative dates (i.e. "+90 mins").
- `status=` - Sets the `Status` HTTP Header.
- `tk=` - Sets the `Tk` HTTP Header.
- `url=` - Sets the URL to use in the `refresh` parameter.
- `vary=` - Sets the `Vary` HTTP Header.
- `via=` - Sets the `Via` HTTP Header.
- `warning=` - Sets the `Warning` HTTP Header.
- `x_content_duration=` - Sets the `X-Content-Duration` HTTP Header.
- `x_content_type_options=` - Sets the `X-Content-Type-Options` HTTP Header.
- `x_frame_options=` - Sets the `X-Frame-Options` HTTP Header.
- `x_ua_compatible=` - Sets the `X-UA-Compatible` HTTP Header.

## Change Log

### 1.0.0

- Initial release. Boom!
