require 'jwt'

key_file = 'key.txt'
# ID tài khoản Apple Developer
team_id = '6FX9L9YT45'
# Identifier của Service ID hoặc Bundle Id của App ID
client_id = 'com.picki.pickleball'
# ID của Key vừa tạo ở bước 3
key_id = 'B98TRZ6F39'

ecdsa_key = OpenSSL::PKey::EC.new IO.read key_file

headers = {
'kid' => key_id
}

claims = {
    'iss' => team_id,
    'iat' => Time.now.to_i,
    'exp' => Time.now.to_i + 86400*180,
    'aud' => 'https://appleid.apple.com',
    'sub' => client_id,
}

token = JWT.encode claims, ecdsa_key, 'ES256', headers

puts token