all:
	thrift -r --gen php thrift/Phan.thrift

clean:
	rm -rf gen-php
