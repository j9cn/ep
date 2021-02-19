#openssl生成密匙
###RSA密钥生成命令

**_生成RSA私钥_** 

`openssl>` genrsa -out /home/rsa_private_key.pem 1024

**_生成RSA公钥_**

`openssl>` rsa -in /home/rsa_private_key.pem -pubout -out /home/rsa_public_key.pem

**_将RSA私钥转换成PKCS8格式_**

`openssl>` pkcs8 -topk8 -inform PEM -in rsa_private_key.pem -outform PEM -nocrypt

###DSA密钥生成命令

**_生成DSA参数及私钥_**（dsa.pem包含参数及私钥）

`openssl>` dsaparam -genkey -out /home/dsa.pem 1024

**_生成DSA公钥_**

`openssl>` dsa -in /home/dsa.pem -pubout -out /home/dsa_public_key.pem