$body = @{
    email = 'devmanasis@sotechy.in'
    name = 'Test User'
    user_type = 'user'
} | ConvertTo-Json

try {
    $response = Invoke-WebRequest -Uri 'http://localhost/api/auth/send-otp.php' -Method POST -Body $body -ContentType 'application/json'
    Write-Host "Status: $($response.StatusCode)"
    Write-Host $response.Content
} catch {
    Write-Host "Error: $_"
    Write-Host $_.Exception.Response.StatusCode
}
