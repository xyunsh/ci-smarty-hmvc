<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 加密解密QQ消息的工具类. QQ消息的加密算法是一个16次的迭代过程，并且是反馈的，每一个加密单元是8字节，输出也是8字节，密钥是16字节
 * 我们以prePlain表示前一个明文块，plain表示当前明文块，crypt表示当前明文块加密得到的密文块，preCrypt表示前一个密文块
 * f表示加密算法，d表示解密算法 那么从plain得到crypt的过程是: crypt = f(plain &circ; preCrypt) &circ;
 * prePlain 所以，从crypt得到plain的过程自然是 plain = d(crypt &circ; prePlain) &circ;
 * preCrypt 此外，算法有它的填充机制，其会在明文前和明文后分别填充一定的字节数，以保证明文长度是8字节的倍数
 * 填充的字节数与原始明文长度有关，填充的方法是:
 * 
 * <pre>
 * <code>
 * 
 *      ------- 消息填充算法 ----------- 
 *      a = (明文长度 + 10) mod 8
 *      if(a 不等于 0) a = 8 - a;
 *      b = 随机数 & 0xF8 | a;              这个的作用是把a的值保存了下来
 *      plain[0] = b;                   然后把b做为明文的第0个字节，这样第0个字节就保存了a的信息，这个信息在解密时就要用来找到真正明文的起始位置
 *      plain[1 至 a+2] = 随机数 &amp; 0xFF;    这里用随机数填充明文的第1到第a+2个字节
 *      plain[a+3 至 a+3+明文长度-1] = 明文; 从a+3字节开始才是真正的明文
 *      plain[a+3+明文长度, 最后] = 0;       在最后，填充0，填充到总长度为8的整数为止。到此为止，结束了，这就是最后得到的要加密的明文内容
 *      ------- 消息填充算法 ------------
 *   
 * </code>
 * </pre>
 * 
 * @author Cator Wei <catorwei@gmail.com>
 */
class Crypter {
    // 指向当前的明文块
    private $plain = array();
    // 这指向前面一个明文块
    private $prePlain = array();
    // 输出的密文或者明文
    private $out = array();
    // 当前加密的密文位置和上一次加密的密文块位置，他们相差8
    private $crypt = 0;
    private $preCrypt = 0;
    // 当前处理的加密解密块的位置
    private $pos = 0;     
    // 填充数
    private $padding = 0;
    // 密钥
    private $key = array();
    // 用于加密时，表示当前是否是第一个8字节块，因为加密算法是反馈的
    // 但是最开始的8个字节没有反馈可用，所有需要标明这种情况
    private $header = true;
    // 这个表示当前解密开始的位置，之所以要这么一个变量是为了避免当解密到最后时
    // 后面已经没有数据，这时候就会出错，这个变量就是用来判断这种情况免得出错
    private $contextStart = 0;

    /**
    * 把字节数组从offset开始的len个字节转换成一个unsigned int， 因为java里面没有unsigned，所以unsigned
    * int使用long表示的， 如果len大于8，则认为len等于8。如果len小于8，则高位填0 <br>
    * (edited by notxx) 改变了算法, 性能稍微好一点. 在我的机器上测试10000次, 原始算法花费18s, 这个算法花费12s.
    * 
    * @param in
    *                   字节数组.
    * @param offset
    *                   从哪里开始转换.
    * @param len
    *                   转换长度, 如果len超过8则忽略后面的
    * @return
    */
    private static function getUnsignedInt($in, $offset, $len) {
        $ret = 0;
        $ret2 = 0;
        $end = 0;
        if ($len > 8) {
            $end = $offset + 8;
        } else {
            $end = $offset + $len;
        }
        for ($j = 0, $i = $offset; $i < $end; $i++, $j++) {
            if ($j<4) {
                $ret <<= 8;
                $ret |= $in[$i] & 0xff;
            } else {
                $ret2 <<= 8;
                $ret2 |= $in[$i] & 0xff;
            }
        }
        return $ret | $ret2;
    }
    
    /**
     * 解密
     * @param in 密文
     * @param offset 密文开始的位置
     * @param len 密文长度
     * @param k 密钥
     * @return 明文
     */
    public function decrypt($in, $offset, $len = 0, $k = '') {
        if (func_num_args() == 2) {
            $k = $offset;
            $offset = 0;
            $len = strlen($in);
        }
        // 检查密钥
        if (empty($k)) {
            return null;
        }

        $in = $this->stringToByteArray($in);
        $k = $this->stringToByteArray($k);
    
        $this->crypt = $this->preCrypt = 0;
        $this->key = $k;
        $count = 0;
        $m = $this->newByteArray($offset + 8);
        
        // 因为QQ消息加密之后至少是16字节，并且肯定是8的倍数，这里检查这种情况
        if (($len % 8 != 0) || ($len < 16)) return null;
        // 得到消息的头部，关键是得到真正明文开始的位置，这个信息存在第一个字节里面，所以其用解密得到的第一个字节与7做与
        $this->prePlain = $this->decipher($in, $offset);
        $this->pos = $this->prePlain[0] & 0x7;
        // 得到真正明文的长度
        $count = $len - $this->pos - 10;
        // 如果明文长度小于0，那肯定是出错了，比如传输错误之类的，返回
        if ($count < 0) return null;
        
        // 这个是临时的preCrypt，和加密时第一个8字节块没有prePlain一样，解密时
        // 第一个8字节块也没有preCrypt，所有这里建一个全0的
        for ($i = $offset; $i < count($m); $i++)
            $m[$i] = 0;
        // 通过了上面的代码，密文应该是没有问题了，我们分配输出缓冲区
        $this->out = $this->newByteArray($count);
        // 设置preCrypt的位置等于0，注意目前的preCrypt位置是指向m的，因为java没有指针，所以我们在后面要控制当前密文buf的引用
        $this->preCrypt = 0;
        // 当前的密文位置，为什么是8不是0呢？注意前面我们已经解密了头部信息了，现在当然该8了
        $this->crypt = 8;
        // 自然这个也是8
        $this->contextStart = 8;
        // 加1，和加密算法是对应的
        $this->pos++;
        
        // 开始跳过头部，如果在这个过程中满了8字节，则解密下一块
        // 因为是解密下一块，所以我们有一个语句 m = in，下一块当然有preCrypt了，我们不再用m了
        // 但是如果不满8，这说明了什么？说明了头8个字节的密文是包含了明文信息的，当然还是要用m把明文弄出来
        // 所以，很显然，满了8的话，说明了头8个字节的密文除了一个长度信息有用之外，其他都是无用的填充
        $this->padding = 1;
        while ($this->padding <= 2) {
            if ($this->pos < 8) {
                $this->pos++;
                $this->padding++;
            }
            if ($this->pos == 8) {
                $m = $in;
                if (!$this->decrypt8Bytes($in, $offset, $len)) return null;
            }
        }
        
        // 这里是解密的重要阶段，这个时候头部的填充都已经跳过了，开始解密
        // 注意如果上面一个while没有满8，这里第一个if里面用的就是原始的m，否则这个m就是in了
        $i = 0;
        while ($count != 0) {
            if ($this->pos < 8) {
                $this->out[$i] = $m[$offset + $this->preCrypt + $this->pos] ^ $this->prePlain[$this->pos];
                $i++;
                $count--;
                $this->pos++;
            }
            if ($this->pos == 8) {
                $m = $in;
                $this->preCrypt = $this->crypt - 8;
                if (!$this->decrypt8Bytes($in, $offset, $len)) 
                    return null;
            }
        }
        
        // 最后的解密部分，上面一个while已经把明文都解出来了，就剩下尾部的填充了，应该全是0
        // 所以这里有检查是否解密了之后是不是0，如果不是的话那肯定出错了，返回null
        for ($this->padding = 1; $this->padding < 8; $this->padding++) {
            if ($this->pos < 8) {
                if(($m[$offset + $this->preCrypt + $this->pos] ^ $this->prePlain[$this->pos]) != 0)
                    return null;
                $this->pos++;
            }
            if ($this->pos == 8) {
                $m = $in;
                $this->preCrypt = $this->crypt;
                if (!$this->decrypt8Bytes($in, $offset, $len)) 
                    return null;
            }
        }
        
        $result = '';
        foreach ($this->out as $c) {
            $result .= chr($c);
        }
        return $result;
    }
    
    /**
     * 加密
     * @param in 明文字节数组
     * @param offset 开始加密的偏移
     * @param len 加密长度
     * @param k 密钥
     * @return 密文字节数组
     */
    public function encrypt($in, $offset, $len = 0, $k = '') {
        if (func_num_args() == 2) {
            $k = $offset;
            $offset = 0;
            $len = strlen($in);
        }
        // 检查密钥
        if (empty($k)) return $in;
        
        $in = $this->stringToByteArray($in);
        $k = $this->stringToByteArray($k);
    
        $this->plain = $this->newByteArray(8);//new byte[8];
        $this->prePlain = $this->newByteArray(8);//new byte[8];
        $this->pos = 1;           
        $this->padding = 0; 
        $this->crypt = $this->preCrypt = 0;
        $this->key = $k;
        $this->header = true;
        
        // 计算头部填充字节数
        $this->pos = ($len + 0x0A) % 8;
        if ($this->pos != 0)
            $this->pos = 8 - $this->pos;
        // 计算输出的密文长度
        $this->out = $this->newByteArray($len + $this->pos + 10);
        // 这里的操作把pos存到了plain的第一个字节里面
        // 0xF8后面三位是空的，正好留给pos，因为pos是0到7的值，表示文本开始的字节位置
        $this->plain[0] = ((rand() & 0xF8) | $this->pos) & 0xFF;
        
        // 这里用随机产生的数填充plain[1]到plain[pos]之间的内容
        for ($i = 1; $i <= $this->pos; $i++)
            $this->plain[$i] = rand() & 0xFF;
        $this->pos++;
        // 这个就是prePlain，第一个8字节块当然没有prePlain，所以我们做一个全0的给第一个8字节块
        for ($i = 0; $i < 8; $i++)
            $this->prePlain[$i] = 0x0;
        
        // 继续填充2个字节的随机数，这个过程中如果满了8字节就加密之
        $this->padding = 1;
        while ($this->padding <= 2) {
            if ($this->pos < 8) {
                $this->plain[$this->pos++] = rand() & 0xFF;
                $this->padding++;
            }
            if ($this->pos == 8)
                $this->encrypt8Bytes();
        }
        
        // 头部填充完了，这里开始填真正的明文了，也是满了8字节就加密，一直到明文读完
        $i = $offset;
        while ($len > 0) {
            if($this->pos < 8) {
                $this->plain[$this->pos++] = $in[$i++];
                $len--;
            }
            if ($this->pos == 8)
                $this->encrypt8Bytes();
        }
        
        // 最后填上0，以保证是8字节的倍数
        $this->padding = 1;
        while ($this->padding <= 7) {
            if ($this->pos < 8) {
                $this->plain[$this->pos++] = 0x0;
                $this->padding++;
            }
            if ($this->pos == 8)
                $this->encrypt8Bytes();
        }
        
        $result = '';
        foreach ($this->out as $c) {
            $result .= chr($c);
        }
        return $result;
    }

    /**
     * 加密一个8字节块
     * 
     * @param in
     * 明文字节数组
     * @return
     * 密文字节数组
     */
    private function encipher($in) {
        // 迭代次数，16次
        $loop = 0x10;
        // 得到明文和密钥的各个部分，注意java没有无符号类型，所以为了表示一个无符号的整数
        // 我们用了long，这个long的前32位是全0的，我们通过这种方式模拟无符号整数，后面用到的long也都是一样的
        // 而且为了保证前32位为0，需要和0xFFFFFFFF做一下位与            
        $y = self::getUnsignedInt($in, 0, 4);
        $z = self::getUnsignedInt($in, 4, 4);
        $a = self::getUnsignedInt($this->key, 0, 4);
        $b = self::getUnsignedInt($this->key, 4, 4);
        $c = self::getUnsignedInt($this->key, 8, 4);
        $d = self::getUnsignedInt($this->key, 12, 4);
        // 这是算法的一些控制变量，为什么delta是0x9E3779B9呢？
        // 这个数是TEA算法的delta，实际是就是(sqr(5) - 1) * 2^31 (根号5，减1，再乘2的31次方)
        $sum = 0;
        $delta = 0x9E3779B9;

        // 开始迭代了，乱七八糟的，我也看不懂，反正和DES之类的差不多，都是这样倒来倒去
        while ($loop-- > 0) {
            $sum = $this->_add($sum, $delta);
            $y = $this->_add($y, $this->_add(($z << 4), $a) ^ $this->_add($z, $sum) ^ $this->_add($this->_rshift($z, 5), $b));
            $z = $this->_add($z, $this->_add(($y << 4), $c) ^ $this->_add($y, $sum) ^ $this->_add($this->_rshift($y, 5), $d));
        }

        // 最后，我们输出密文，因为我用的long，所以需要强制转换一下变成int
        return array_merge($this->writeInt($y), $this->writeInt($z));
    }
    
    /**
     * 解密从offset开始的8字节密文
     * 
     * @param in
     * 密文字节数组
     * @param offset
     * 密文开始位置
     * @return
     * 明文
     */
//    private byte[] decipher(byte[] in, int offset) {
    private function decipher($in, $offset = 0) {
        // 迭代次数，16次
        $loop = 0x10;
        // 得到密文和密钥的各个部分，注意java没有无符号类型，所以为了表示一个无符号的整数
        // 我们用了long，这个long的前32位是全0的，我们通过这种方式模拟无符号整数，后面用到的long也都是一样的
        // 而且为了保证前32位为0，需要和0xFFFFFFFF做一下位与
        $y = self::getUnsignedInt($in, $offset, 4);
        $z = self::getUnsignedInt($in, $offset + 4, 4);
        $a = self::getUnsignedInt($this->key, 0, 4);
        $b = self::getUnsignedInt($this->key, 4, 4);
        $c = self::getUnsignedInt($this->key, 8, 4);
        $d = self::getUnsignedInt($this->key, 12, 4);
//        echo $a,',',$b,',',$c,',',$d,',',$y,',',$z,',<br>';
        // 算法的一些控制变量，sum在这里也有数了，这个sum和迭代次数有关系
        // 因为delta是这么多，所以sum如果是这么多的话，迭代的时候减减减，减16次，最后
        // 得到0。反正这就是为了得到和加密时相反顺序的控制变量，这样才能解密呀～～
        $sum = 0xE3779B90;
        $delta = 0x9E3779B9;

        // 迭代开始了， @_@
        while($loop-- > 0) {
            $z = $this->_add($z, - ($this->_add(($y << 4), $c) ^ $this->_add($y , $sum) ^ $this->_add($this->_rshift($y, 5), $d)));
            $y = $this->_add($y, - ($this->_add(($z << 4), $a) ^ $this->_add($z , $sum) ^ $this->_add($this->_rshift($z, 5), $b)));
            $sum = $this->_add($sum, - $delta);
        }

        return array_merge($this->writeInt($y), $this->writeInt($z));
    }
    
    private function _rshift($integer, $n) {
        // convert to 32 bits
        if (0xffffffff < $integer || - 0xffffffff > $integer) {
            $integer = fmod ( $integer, 0xffffffff + 1 );
        }

        // convert to unsigned integer
        if (0x7fffffff < $integer) {
            $integer -= 0xffffffff + 1.0;
        } elseif (- 0x80000000 > $integer){
            $integer += 0xffffffff + 1.0;
        }

        // do right shift
        if (0 > $integer) {
            $integer &= 0x7fffffff; // remove sign bit before shift
            $integer >>= $n; // right shift
            $integer |= 1 << (31 - $n); // set shifted sign bit
        } else {
            $integer >>= $n; // use normal right shift
        }

        return $integer;
    }
    
    public function _add($i1, $i2) {
        $result = 0.0;

        foreach ( func_get_args () as $value ) {
            if (0.0 > $value) {
                $value -= 1.0 + 0xffffffff;
            }
            $result += $value;
        }

        if (0xffffffff < $result || - 0xffffffff > $result) {
            $result = fmod ( $result, 0xffffffff + 1 );
        }

        if (0x7fffffff < $result) {
            $result -= 0xffffffff + 1.0;
        } elseif (- 0x80000000 > $result) {
            $result += 0xffffffff + 1.0;
        }

        return $result;
    }
    
    /**
     * 写入一个整型到输出流，高字节优先
     * 
     * @param t
     */
    private function writeInt($t) {
        $a = ($t >> 24) & 0xFF;
        $b = ($t >> 16) & 0xFF;
        $c = ($t >> 8) & 0xFF;
        $d = $t & 0xFF;
        return array($a, $b, $c, $d);
    }
    
    /**
     * 加密8字节 
     */
    private function encrypt8Bytes() {
        // 这部分完成我上面所说的 plain ^ preCrypt，注意这里判断了是不是第一个8字节块，如果是的话，那个prePlain就当作preCrypt用
        for($this->pos = 0; $this->pos < 8; $this->pos++) {
            if($this->header) 
                $this->plain[$this->pos] ^= $this->prePlain[$this->pos];
            else
                $this->plain[$this->pos] ^= $this->out[$this->preCrypt + $this->pos];
        }
        // 这个完成我上面说的 f(plain ^ preCrypt)
        $crypted = $this->encipher($this->plain);
        // 这个没什么，就是拷贝一下，java不像c，所以我只好这么干，c就不用这一步了
        array_splice($this->out, $this->crypt, 8, $crypted);
        
        // 这个完成了 f(plain ^ preCrypt) ^ prePlain，ok，下面拷贝一下就行了
        for ($this->pos = 0; $this->pos < 8; $this->pos++) {
            $this->out[$this->crypt + $this->pos] ^= $this->prePlain[$this->pos];
        }
        array_splice($this->prePlain, 0, 8, $this->plain);
        
        // 完成了加密，现在是调整crypt，preCrypt等等东西的时候了
        $this->preCrypt = $this->crypt;
        $this->crypt += 8;      
        $this->pos = 0;
        $this->header = false;            
    }

    /**
     * 解密8个字节
     * 
     * @param in
     * 密文字节数组
     * @param offset
     * 从何处开始解密
     * @param len
     * 密文的长度
     * @return
     *	true表示解密成功
     */
    private function decrypt8Bytes($in , $offset, $len) {
        // 这里第一步就是判断后面还有没有数据，没有就返回，如果有，就执行 crypt ^ prePlain
        for($this->pos = 0; $this->pos < 8; $this->pos++) {
            if($this->contextStart + $this->pos >= $len) 
                return true;
            $this->prePlain[$this->pos] ^= $in[$offset + $this->crypt + $this->pos];
        }
        
        // 好，这里执行到了 d(crypt ^ prePlain)
        $this->prePlain = $this->decipher($this->prePlain);
        if($this->prePlain == null)
        return false;
        
        // 解密完成，最后一步好像没做？ 
        // 这里最后一步放到decrypt里面去做了，因为解密的步骤有点不太一样
        // 调整这些变量的值先
        $this->contextStart += 8;
        $this->crypt += 8;
        $this->pos = 0;
        return true;
    }
    
    private function newByteArray($len, $default = 0) {
        $result = array();
        for ($i=0; $i<$len; $i++) {
            $result[$i] = $default;
        }
        return $result;
    }

    private function stringToByteArray($s) {
        $result = array();
        $len = strlen($s);
        for ($i=0; $i<$len; $i++) {
            $result[$i] = ord($s[$i]);
        }
        return $result;
    }
}