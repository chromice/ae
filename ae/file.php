<?php

#
# Copyright 2011-2015 Anton Muraviev <chromice@gmail.com>
# 
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
# 
#     http://www.apache.org/licenses/LICENSE-2.0
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
# 

namespace ae;

\ae::invoke('\ae\File');

class File
/*
	A thin wrapper that abstracts common file operations 
	mostly for the sake of exception safety.
	
		$file = ae::file('example.txt')
			->open('w')
			->write('This is a test.')
			->close();
	
	All methods throw `FileException` on failure.
*/
{
	protected $path;
	protected $name;
	protected $type;
	protected $meta;
	
	protected $file;
	protected $is_locked = false;
	
	public function __construct($path, $name = null, $meta = [])
	{
		if (is_array($name) && empty($meta))
		{
			$meta = $name;
			$name = null;
		}
		
		$this->path = $path;
		$this->meta = is_array($meta) ? $meta : [];
		$this->name = pathinfo(is_null($name) ? $path : $name, PATHINFO_FILENAME);
		$this->extension = pathinfo(is_null($name) ? $path : $name, PATHINFO_EXTENSION);
	}
	
	public function __destruct()
	{
		if (is_resource($this->file))
		{
			$this->close();
		}
	}
	
	public function exists()
	{
		return is_file($this->path);
	}
	
	public function is_uploaded()
	{
		return is_uploaded_file($this->path);
	}
	
	
	// =============
	// = File info =
	// =============
	
	public function path()
	{
		return $this->path;
	}
	
	public function name()
	{
		return $this->name;
	}
	
	public function meta($name = null, $value = null)
	{
		if (!is_null($name) && !is_null($value))
		{
			$this->meta[$name] = $value;
			
			return $this;
		}
		elseif (!is_null($name))
		{
			return isset($this->meta[$name]) ? $this->meta[$name] : null;
		}
		
		return $this->meta;
	}
	
	public function extension($validate = true)
	{
		if ($validate === false)
		{
			return $this->extension;
		}
		
		$extension = strtolower($this->extension);
		$mime = $this->mime();
		
		$found = self::find_matching_types($mime, $extension);
		$found = array_pop($found);
		
		if (isset($found[1]))
		{
			return $found[1];
		}
		
		if (!empty($extension))
		{
			throw new FileException('No valid file extension found for MIME: ' . $mime);
		}
		else
		{
			throw new FileException($extension . ' is an invalid file extension for MIME: ' . $mime);
		}
	}
	
	public static function find_matching_types($mime, $extension)
	{
		if (empty($mime) && empty($extension))
		{
			trigger_error('Cannot find matching type candidates: no MIME or extension were specified.');
		}
		
		return array_filter(self::$types, function ($candidate) use ($mime, $extension) {
			return (empty($mime) || $candidate[0] === $mime)
				&& (empty($extension) || $candidate[1] === $extension);
		});
	}
	
	public function full_name($validate = true)
	{
		return $this->name() . '.' . $this->extension($validate);
	}
	
	public function mime()
	{
		$info = new \finfo(FILEINFO_MIME_TYPE);
		
		return $info->file($this->path);
	}
	
	public function size()
	{
		return filesize($this->path);
	}
	
	public function width()
	{
		$info = getimagesize($this->path);
		
		return $info && isset($info[0]) ? $info[0] : null;
	}
	
	public function height()
	{
		$info = getimagesize($this->path);
		
		return $info && isset($info[1]) ? $info[1] : null;
	}
	
	public function hash()
	{
		return sha1_file($this->path);
	}
	
	
	// =================
	// = FS operations =
	// =================
	
	public function move($destination, $overwrite = false)
	{
		$this->_cannot('move file');
		
		$path = self::_destination(
			$destination,
			$this->name(),
			$this->extension(false),
			$overwrite
		);
		
		if (!$this->is_uploaded() && false === @rename($this->path, $path)
		|| $this->is_uploaded() && false === @move_uploaded_file($this->path, $path))
		{
			throw new FileException('Failed to move file.');
		}
		
		$this->path = $path;
		
		return $this;
	}
	
	public function copy($destination, $overwrite = false)
	{
		$this->_cannot('copy file');
		
		$path = self::_destination(
			$destination,
			$this->name(),
			$this->extension(false),
			$overwrite
		);
		
		if (false === @copy($this->path, $path))
		{
			throw new FileException('Failed to copy file.');
		}
		
		return new File($path, null, $this->meta);
	}
	
	public function delete()
	{
		$this->_cannot('delete file');
		
		if (false === @unlink($this->path))
		{
			throw new FileException('Failed to delete file.');
		}
		
		return $this;
	}
	
	protected static function _destination($destination, $name, $extension, $overwrite)
	{
		// FIXME: Destination may have subdirectories missing.
		if (!is_dir($destination) && is_dir(pathinfo($destination, PATHINFO_DIRNAME)))
		{
			$name = pathinfo($destination, PATHINFO_FILENAME);
			$extension = pathinfo($destination, PATHINFO_EXTENSION);
			
			$destination = pathinfo($destination, PATHINFO_DIRNAME);
		}
		
		$i = 0;
		$path = rtrim($destination, '/') . '/' . $name . '.' . $extension;
		
		while (file_exists($path) && !$overwrite)
		{
			$path = rtrim($destination, '/') . '/' . $name . ' ' . ++$i . '.' . $extension;
		}
		
		return $path;
	}
	
	
	// =================
	// = IO operations =
	// =================
	
	public function open($mode, $use_include_path = false, $context = null)
	{
		if (is_resource($this->file))
		{
			throw new FileException('File is already opened.');
		}
		
		$this->file = is_resource($context)
			? fopen($this->path, $mode, $use_include_path, $context)
			: fopen($this->path, $mode, $use_include_path);
		
		if (false === $this->file)
		{
			throw new FileException('Failed to open file.');
		}
		
		return $this;
	}
	
	public function close()
	{
		$this->_can('close file');
		
		if ($this->is_locked)
		{
			$this->unlock();
		}
		
		if (false === fclose($this->file))
		{
			throw new FileException('Failed to close file.');
		}
		
		$this->file = null;
		
		return $this;
	}
	
	public function lock($mode = null)
	{
		$this->_can('lock file');
		
		if ($this->is_locked)
		{
			$this->unlock();
		}
		
		if (is_null($mode))
		{
			$mode = LOCK_EX | LOCK_NB;
		}
		
		if (false === flock($this->file, $mode)) 
		{
			throw new FileException('Failed to lock file.');
		}
		
		$this->is_locked = true;
		
		return $this;
	}
	
	public function unlock()
	{
		$this->_can('unlock file');
		
		if (!$this->is_locked)
		{
			return $this;
		}
		
		if (false === flock($this->file, LOCK_UN))
		{
			throw new FileException('Failed to unlock file.');
		}
		
		$this->is_locked = false;
		
		return $this;
	}
	
	public function truncate($size = 0)
	{
		$this->_can('truncate file');
		
		if (false === ftruncate($this->file, $size))
		{
			throw new FileException('Failed to truncate file.');
		}
		
		return $this;
	}
	
	public function write($content, $length = null)
	{
		$this->_can('write to file');
		
		if (false === (is_null($length)
			? fwrite($this->file, $content)
			: fwrite($this->file, $content, $length)))
		{
			throw new FileException('Failed to write to file.');
		}
		
		return $this;
	}
	
	public function read($length)
	{
		$this->_can('read from file');
		
		if (false === ($read = fread($this->file, $length)))
		{
			throw new FileException('Failed to read from file.');
		}
		
		return $read;
	}
	
	public function seek($offset, $whence = SEEK_SET)
	{
		$this->_can('seek the position');
		
		if (-1 === fseek($this->file, $offset, $whence))
		{
			throw new FileException('Failed to seek the position.');
		}
		
		return $this;
	}
	
	public function tell()
	{
		$this->_can('tell the position');
		
		if (flase === ($offset = ftell($this->file)))
		{
			throw new FileException('Failed to return the offset.');
		}
		
		return $offset;
	}
	
	protected function _can($intent)
	{
		if (!is_resource($this->file))
		{
			throw new FileException('Cannot ' . $intent . '. File is not opened.');
		}
	}
	protected function _cannot($intent)
	{
		if (is_resource($this->file))
		{
			throw new FileException('Cannot ' . $intent . '. File is opened.');
		}
	}
	
	protected static $types = [
		['application/x-bytecode.python', 'pyc'],
		['application/acad', 'dwg'],
		['application/arj', 'arj'],
		['application/base64', 'mm'],
		['application/base64', 'mme'],
		['application/binhex', 'hqx'],
		['application/binhex4', 'hqx'],
		['application/book', 'book'],
		['application/book', 'boo'],
		['application/cdf', 'cdf'],
		['application/clariscad', 'ccad'],
		['application/commonground', 'dp'],
		['application/drafting', 'drw'],
		['application/dsptype', 'tsp'],
		['application/dxf', 'dxf'],
		['application/ecmascript', 'js'],
		['application/envoy', 'evy'],
		['application/excel', 'xls'],
		['application/excel', 'xl'],
		['application/excel', 'xla'],
		['application/excel', 'xlb'],
		['application/excel', 'xlc'],
		['application/excel', 'xld'],
		['application/excel', 'xlk'],
		['application/excel', 'xll'],
		['application/excel', 'xlm'],
		['application/excel', 'xlt'],
		['application/excel', 'xlv'],
		['application/excel', 'xlw'],
		['application/fractals', 'fif'],
		['application/freeloader', 'frl'],
		['application/futuresplash', 'spl'],
		['application/gnutar', 'tgz'],
		['application/groupwise', 'vew'],
		['application/hlp', 'hlp'],
		['application/hta', 'hta'],
		['application/i-deas', 'unv'],
		['application/iges', 'iges'],
		['application/iges', 'igs'],
		['application/inf', 'inf'],
		['application/java', 'class'],
		['application/java-byte-code', 'class'],
		['application/javascript', 'js'],
		['application/lha', 'lha'],
		['application/lzx', 'lzx'],
		['application/mac-binary', 'bin'],
		['application/mac-binhex', 'hqx'],
		['application/mac-binhex40', 'hqx'],
		['application/mac-compactpro', 'cpt'],
		['application/macbinary', 'bin'],
		['application/marc', 'mrc'],
		['application/mbedlet', 'mbd'],
		['application/mcad', 'mcd'],
		['application/mime', 'aps'],
		['application/mspowerpoint', 'pot'],
		['application/mspowerpoint', 'pps'],
		['application/mspowerpoint', 'ppt'],
		['application/mspowerpoint', 'ppz'],
		['application/msword', 'doc'],
		['application/msword', 'dot'],
		['application/msword', 'w6w'],
		['application/msword', 'wiz'],
		['application/msword', 'word'],
		['application/mswrite', 'wri'],
		['application/netmc', 'mcp'],
		['application/octet-stream', 'a'],
		['application/octet-stream', 'arc'],
		['application/octet-stream', 'arj'],
		['application/octet-stream', 'bin'],
		['application/octet-stream', 'com'],
		['application/octet-stream', 'dump'],
		['application/octet-stream', 'exe'],
		['application/octet-stream', 'lha'],
		['application/octet-stream', 'lhx'],
		['application/octet-stream', 'lzh'],
		['application/octet-stream', 'lzx'],
		['application/octet-stream', 'o'],
		['application/octet-stream', 'psd'],
		['application/octet-stream', 'saveme'],
		['application/octet-stream', 'uu'],
		['application/octet-stream', 'zoo'],
		['application/oda', 'oda'],
		['application/pdf', 'pdf'],
		['application/pkcs-12', 'p12'],
		['application/pkcs-crl', 'crl'],
		['application/pkcs10', 'p10'],
		['application/pkcs7-mime', 'p7m'],
		['application/pkcs7-mime', 'p7c'],
		['application/pkcs7-signature', 'p7s'],
		['application/pkix-cert', 'cer'],
		['application/pkix-cert', 'crt'],
		['application/pkix-crl', 'crl'],
		['application/plain', 'text'],
		['application/postscript', 'ps'],
		['application/postscript', 'ai'],
		['application/postscript', 'eps'],
		['application/powerpoint', 'ppt'],
		['application/pro_eng', 'part'],
		['application/pro_eng', 'prt'],
		['application/ringing-tones', 'rng'],
		['application/rtf', 'rtf'],
		['application/rtf', 'rtx'],
		['application/sdp', 'sdp'],
		['application/sea', 'sea'],
		['application/set', 'set'],
		['application/sla', 'stl'],
		['application/smil', 'smi'],
		['application/smil', 'smil'],
		['application/solids', 'sol'],
		['application/sounder', 'sdr'],
		['application/step', 'step'],
		['application/step', 'stp'],
		['application/streamingmedia', 'ssm'],
		['application/toolbook', 'tbk'],
		['application/vda', 'vda'],
		['application/vnd.fdf', 'fdf'],
		['application/vnd.hp-hpgl', 'hgl'],
		['application/vnd.hp-hpgl', 'hpg'],
		['application/vnd.hp-hpgl', 'hpgl'],
		['application/vnd.hp-pcl', 'pcl'],
		['application/vnd.ms-excel', 'xls'],
		['application/vnd.ms-excel', 'xlb'],
		['application/vnd.ms-excel', 'xlc'],
		['application/vnd.ms-excel', 'xll'],
		['application/vnd.ms-excel', 'xlm'],
		['application/vnd.ms-excel', 'xlw'],
		['application/vnd.ms-pki.certstore', 'sst'],
		['application/vnd.ms-pki.pko', 'pko'],
		['application/vnd.ms-pki.seccat', 'cat'],
		['application/vnd.ms-pki.stl', 'stl'],
		['application/vnd.ms-powerpoint', 'ppt'],
		['application/vnd.ms-powerpoint', 'pot'],
		['application/vnd.ms-powerpoint', 'ppa'],
		['application/vnd.ms-powerpoint', 'pps'],
		['application/vnd.ms-powerpoint', 'pwz'],
		['application/vnd.ms-project', 'mpp'],
		['application/vnd.nokia.configuration-message', 'ncm'],
		['application/vnd.nokia.ringing-tone', 'rng'],
		['application/vnd.rn-realmedia', 'rm'],
		['application/vnd.rn-realplayer', 'rnx'],
		['application/vnd.wap.wmlc', 'wmlc'],
		['application/vnd.wap.wmlscriptc', 'wmlsc'],
		['application/vnd.xara', 'web'],
		['application/vocaltec-media-desc', 'vmd'],
		['application/vocaltec-media-file', 'vmf'],
		['application/wordperfect', 'wp'],
		['application/wordperfect', 'wp5'],
		['application/wordperfect', 'wp6'],
		['application/wordperfect', 'wpd'],
		['application/wordperfect6.0', 'w60'],
		['application/wordperfect6.0', 'wp5'],
		['application/wordperfect6.1', 'w61'],
		['application/x-123', 'wk1'],
		['application/x-aim', 'aim'],
		['application/x-authorware-bin', 'aab'],
		['application/x-authorware-map', 'aam'],
		['application/x-authorware-seg', 'aas'],
		['application/x-bcpio', 'bcpio'],
		['application/x-binary', 'bin'],
		['application/x-binhex40', 'hqx'],
		['application/x-bsh', 'bsh'],
		['application/x-bsh', 'sh'],
		['application/x-bsh', 'shar'],
		['application/x-bytecode.elisp (compiled elisp)', 'elc'],
		['application/x-bzip', 'bz'],
		['application/x-bzip2', 'bz2'],
		['application/x-bzip2', 'boz'],
		['application/x-cdf', 'cdf'],
		['application/x-cdlink', 'vcd'],
		['application/x-chat', 'cha'],
		['application/x-chat', 'chat'],
		['application/x-cmu-raster', 'ras'],
		['application/x-cocoa', 'cco'],
		['application/x-compactpro', 'cpt'],
		['application/x-compress', 'z'],
		['application/x-compressed', 'z'],
		['application/x-compressed', 'gz'],
		['application/x-compressed', 'tgz'],
		['application/x-compressed', 'zip'],
		['application/x-conference', 'nsc'],
		['application/x-cpio', 'cpio'],
		['application/x-cpt', 'cpt'],
		['application/x-csh', 'csh'],
		['application/x-deepv', 'deepv'],
		['application/x-director', 'dir'],
		['application/x-director', 'dcr'],
		['application/x-director', 'dxr'],
		['application/x-dvi', 'dvi'],
		['application/x-elc', 'elc'],
		['application/x-envoy', 'env'],
		['application/x-envoy', 'evy'],
		['application/x-esrehber', 'es'],
		['application/x-excel', 'xls'],
		['application/x-excel', 'xla'],
		['application/x-excel', 'xlb'],
		['application/x-excel', 'xlc'],
		['application/x-excel', 'xld'],
		['application/x-excel', 'xlk'],
		['application/x-excel', 'xll'],
		['application/x-excel', 'xlm'],
		['application/x-excel', 'xlt'],
		['application/x-excel', 'xlv'],
		['application/x-excel', 'xlw'],
		['application/x-frame', 'mif'],
		['application/x-freelance', 'pre'],
		['application/x-gsp', 'gsp'],
		['application/x-gss', 'gss'],
		['application/x-gtar', 'gtar'],
		['application/x-gzip', 'gz'],
		['application/x-gzip', 'gzip'],
		['application/x-hdf', 'hdf'],
		['application/x-helpfile', 'help'],
		['application/x-helpfile', 'hlp'],
		['application/x-httpd-imap', 'imap'],
		['application/x-ima', 'ima'],
		['application/x-internett-signup', 'ins'],
		['application/x-inventor', 'iv'],
		['application/x-ip2', 'ip'],
		['application/x-java-class', 'class'],
		['application/x-java-commerce', 'jcm'],
		['application/x-javascript', 'js'],
		['application/x-koan', 'skd'],
		['application/x-koan', 'skm'],
		['application/x-koan', 'skp'],
		['application/x-koan', 'skt'],
		['application/x-ksh', 'ksh'],
		['application/x-latex', 'latex'],
		['application/x-latex', 'ltx'],
		['application/x-lha', 'lha'],
		['application/x-lisp', 'lsp'],
		['application/x-livescreen', 'ivy'],
		['application/x-lotus', 'wq1'],
		['application/x-lotusscreencam', 'scm'],
		['application/x-lzh', 'lzh'],
		['application/x-lzx', 'lzx'],
		['application/x-mac-binhex40', 'hqx'],
		['application/x-macbinary', 'bin'],
		['application/x-magic-cap-package-1.0', 'mc$'],
		['application/x-mathcad', 'mcd'],
		['application/x-meme', 'mm'],
		['application/x-midi', 'midi'],
		['application/x-midi', 'mid'],
		['application/x-mif', 'mif'],
		['application/x-mix-transfer', 'nix'],
		['application/x-mplayer2', 'asx'],
		['application/x-msexcel', 'xla'],
		['application/x-msexcel', 'xls'],
		['application/x-msexcel', 'xlw'],
		['application/x-mspowerpoint', 'ppt'],
		['application/x-navi-animation', 'ani'],
		['application/x-navidoc', 'nvd'],
		['application/x-navimap', 'map'],
		['application/x-navistyle', 'stl'],
		['application/x-netcdf', 'cdf'],
		['application/x-netcdf', 'nc'],
		['application/x-newton-compatible-pkg', 'pkg'],
		['application/x-nokia-9000-communicator-add-on-software', 'aos'],
		['application/x-omc', 'omc'],
		['application/x-omcdatamaker', 'omcd'],
		['application/x-omcregerator', 'omcr'],
		['application/x-pagemaker', 'pm5'],
		['application/x-pagemaker', 'pm4'],
		['application/x-pcl', 'pcl'],
		['application/x-pixclscript', 'plx'],
		['application/x-pkcs10', 'p10'],
		['application/x-pkcs12', 'p12'],
		['application/x-pkcs7-certificates', 'spc'],
		['application/x-pkcs7-certreqresp', 'p7r'],
		['application/x-pkcs7-mime', 'p7c'],
		['application/x-pkcs7-mime', 'p7m'],
		['application/x-pkcs7-signature', 'p7a'],
		['application/x-pointplus', 'css'],
		['application/x-portable-anymap', 'pnm'],
		['application/x-project', 'mpc'],
		['application/x-project', 'mpt'],
		['application/x-project', 'mpv'],
		['application/x-project', 'mpx'],
		['application/x-qpro', 'wb1'],
		['application/x-rtf', 'rtf'],
		['application/x-sdp', 'sdp'],
		['application/x-sea', 'sea'],
		['application/x-seelogo', 'sl'],
		['application/x-sh', 'sh'],
		['application/x-shar', 'shar'],
		['application/x-shar', 'sh'],
		['application/x-shockwave-flash', 'swf'],
		['application/x-sit', 'sit'],
		['application/x-sprite', 'sprite'],
		['application/x-sprite', 'spr'],
		['application/x-stuffit', 'sit'],
		['application/x-sv4cpio', 'sv4cpio'],
		['application/x-sv4crc', 'sv4crc'],
		['application/x-tar', 'tar'],
		['application/x-tbook', 'sbk'],
		['application/x-tbook', 'tbk'],
		['application/x-tcl', 'tcl'],
		['application/x-tex', 'tex'],
		['application/x-texinfo', 'texinfo'],
		['application/x-texinfo', 'texi'],
		['application/x-troff', 'roff'],
		['application/x-troff', 't'],
		['application/x-troff', 'tr'],
		['application/x-troff-man', 'man'],
		['application/x-troff-me', 'me'],
		['application/x-troff-ms', 'ms'],
		['application/x-troff-msvideo', 'avi'],
		['application/x-ustar', 'ustar'],
		['application/x-visio', 'vsd'],
		['application/x-visio', 'vst'],
		['application/x-visio', 'vsw'],
		['application/x-vnd.audioexplosion.mzz', 'mzz'],
		['application/x-vnd.ls-xpix', 'xpix'],
		['application/x-vrml', 'vrml'],
		['application/x-wais-source', 'wsrc'],
		['application/x-wais-source', 'src'],
		['application/x-winhelp', 'hlp'],
		['application/x-wintalk', 'wtk'],
		['application/x-world', 'wrl'],
		['application/x-world', 'svr'],
		['application/x-wpwin', 'wpd'],
		['application/x-wri', 'wri'],
		['application/x-x509-ca-cert', 'cer'],
		['application/x-x509-ca-cert', 'crt'],
		['application/x-x509-ca-cert', 'der'],
		['application/x-x509-user-cert', 'crt'],
		['application/x-zip-compressed', 'zip'],
		['application/xml', 'xml'],
		['application/zip', 'zip'],
		['audio/aiff', 'aiff'],
		['audio/aiff', 'aifc'],
		['audio/aiff', 'aif'],
		['audio/basic', 'au'],
		['audio/basic', 'snd'],
		['audio/it', 'it'],
		['audio/make', 'funk'],
		['audio/make', 'my'],
		['audio/make', 'pfunk'],
		['audio/make.my.funk', 'pfunk'],
		['audio/mid', 'rmi'],
		['audio/midi', 'midi'],
		['audio/midi', 'mid'],
		['audio/midi', 'kar'],
		['audio/mod', 'mod'],
		['audio/mpeg', 'm2a'],
		['audio/mpeg', 'mp2'],
		['audio/mpeg', 'mpa'],
		['audio/mpeg', 'mpg'],
		['audio/mpeg', 'mpga'],
		['audio/mpeg3', 'mp3'],
		['audio/nspaudio', 'la'],
		['audio/nspaudio', 'lma'],
		['audio/s3m', 's3m'],
		['audio/tsp-audio', 'tsi'],
		['audio/tsplayer', 'tsp'],
		['audio/vnd.qcelp', 'qcp'],
		['audio/voc', 'voc'],
		['audio/voxware', 'vox'],
		['audio/wav', 'wav'],
		['audio/x-adpcm', 'snd'],
		['audio/x-aiff', 'aiff'],
		['audio/x-aiff', 'aif'],
		['audio/x-aiff', 'aifc'],
		['audio/x-au', 'au'],
		['audio/x-gsm', 'gsm'],
		['audio/x-gsm', 'gsd'],
		['audio/x-jam', 'jam'],
		['audio/x-liveaudio', 'lam'],
		['audio/x-mid', 'midi'],
		['audio/x-mid', 'mid'],
		['audio/x-midi', 'midi'],
		['audio/x-midi', 'mid'],
		['audio/x-mod', 'mod'],
		['audio/x-mpeg', 'mp2'],
		['audio/x-mpeg-3', 'mp3'],
		['audio/x-mpequrl', 'm3u'],
		['audio/x-nspaudio', 'la'],
		['audio/x-nspaudio', 'lma'],
		['audio/x-pn-realaudio', 'ra'],
		['audio/x-pn-realaudio', 'ram'],
		['audio/x-pn-realaudio', 'rm'],
		['audio/x-pn-realaudio', 'rmm'],
		['audio/x-pn-realaudio', 'rmp'],
		['audio/x-pn-realaudio-plugin', 'ra'],
		['audio/x-pn-realaudio-plugin', 'rmp'],
		['audio/x-pn-realaudio-plugin', 'rpm'],
		['audio/x-psid', 'sid'],
		['audio/x-realaudio', 'ra'],
		['audio/x-twinvq', 'vqf'],
		['audio/x-twinvq-plugin', 'vqe'],
		['audio/x-twinvq-plugin', 'vql'],
		['audio/x-vnd.audioexplosion.mjuicemediafile', 'mjf'],
		['audio/x-voc', 'voc'],
		['audio/x-wav', 'wav'],
		['audio/xm', 'xm'],
		['chemical/x-pdb', 'pdb'],
		['chemical/x-pdb', 'xyz'],
		['drawing/x-dwf (old)', 'dwf'],
		['i-world/i-vrml', 'ivr'],
		['image/bmp', 'bmp'],
		['image/bmp', 'bm'],
		['image/cmu-raster', 'ras'],
		['image/cmu-raster', 'rast'],
		['image/fif', 'fif'],
		['image/florian', 'flo'],
		['image/florian', 'turbot'],
		['image/g3fax', 'g3'],
		['image/gif', 'gif'],
		['image/ief', 'ief'],
		['image/ief', 'iefs'],
		['image/jpeg', 'jpeg'],
		['image/jpeg', 'jpg'],
		['image/jpeg', 'jfif'],
		['image/jpeg', 'jfif-tbnl'],
		['image/jpeg', 'jpe'],
		['image/jutvision', 'jut'],
		['image/naplps', 'naplps'],
		['image/naplps', 'nap'],
		['image/pict', 'pict'],
		['image/pict', 'pic'],
		['image/pjpeg', 'jpeg'],
		['image/pjpeg', 'jfif'],
		['image/pjpeg', 'jpe'],
		['image/pjpeg', 'jpg'],
		['image/png', 'png'],
		['image/png', 'x-png'],
		['image/tiff', 'tiff'],
		['image/tiff', 'tif'],
		['image/vasa', 'mcf'],
		['image/vnd.dwg', 'dwg'],
		['image/vnd.dwg', 'dxf'],
		['image/vnd.dwg', 'svf'],
		['image/vnd.fpx', 'fpx'],
		['image/vnd.net-fpx', 'fpx'],
		['image/vnd.rn-realflash', 'rf'],
		['image/vnd.rn-realpix', 'rp'],
		['image/vnd.wap.wbmp', 'wbmp'],
		['image/vnd.xiff', 'xif'],
		['image/x-cmu-raster', 'ras'],
		['image/x-dwg', 'dwg'],
		['image/x-dwg', 'dxf'],
		['image/x-dwg', 'svf'],
		['image/x-icon', 'ico'],
		['image/x-jg', 'art'],
		['image/x-jps', 'jps'],
		['image/x-niff', 'niff'],
		['image/x-niff', 'nif'],
		['image/x-pcx', 'pcx'],
		['image/x-pict', 'pct'],
		['image/x-portable-anymap', 'pnm'],
		['image/x-portable-bitmap', 'pbm'],
		['image/x-portable-graymap', 'pgm'],
		['image/x-portable-greymap', 'pgm'],
		['image/x-portable-pixmap', 'ppm'],
		['image/x-quicktime', 'qif'],
		['image/x-quicktime', 'qti'],
		['image/x-quicktime', 'qtif'],
		['image/x-rgb', 'rgb'],
		['image/x-tiff', 'tiff'],
		['image/x-tiff', 'tif'],
		['image/x-windows-bmp', 'bmp'],
		['image/x-xbitmap', 'xbm'],
		['image/x-xbm', 'xbm'],
		['image/x-xpixmap', 'xpm'],
		['image/x-xpixmap', 'pm'],
		['image/x-xwd', 'xwd'],
		['image/x-xwindowdump', 'xwd'],
		['image/xbm', 'xbm'],
		['image/xpm', 'xpm'],
		['message/rfc822', 'mht'],
		['message/rfc822', 'mhtml'],
		['message/rfc822', 'mime'],
		['model/iges', 'iges'],
		['model/iges', 'igs'],
		['model/vnd.dwf', 'dwf'],
		['model/vrml', 'vrml'],
		['model/vrml', 'wrl'],
		['model/vrml', 'wrz'],
		['model/x-pov', 'pov'],
		['multipart/x-gzip', 'gzip'],
		['multipart/x-ustar', 'ustar'],
		['multipart/x-zip', 'zip'],
		['music/crescendo', 'mid'],
		['music/crescendo', 'midi'],
		['music/x-karaoke', 'kar'],
		['paleovu/x-pv', 'pvu'],
		['text/asp', 'asp'],
		['text/css', 'css'],
		['text/ecmascript', 'js'],
		['text/html', 'acgi'],
		['text/html', 'htm'],
		['text/html', 'html'],
		['text/html', 'htmls'],
		['text/html', 'htx'],
		['text/html', 'shtml'],
		['text/javascript', 'js'],
		['text/mcf', 'mcf'],
		['text/pascal', 'pas'],
		['text/plain', 'txt'],
		['text/plain', 'c'],
		['text/plain', 'c++'],
		['text/plain', 'cc'],
		['text/plain', 'com'],
		['text/plain', 'conf'],
		['text/plain', 'cxx'],
		['text/plain', 'def'],
		['text/plain', 'f'],
		['text/plain', 'f90'],
		['text/plain', 'for'],
		['text/plain', 'g'],
		['text/plain', 'h'],
		['text/plain', 'hh'],
		['text/plain', 'idc'],
		['text/plain', 'jav'],
		['text/plain', 'java'],
		['text/plain', 'list'],
		['text/plain', 'log'],
		['text/plain', 'lst'],
		['text/plain', 'm'],
		['text/plain', 'mar'],
		['text/plain', 'pl'],
		['text/plain', 'sdml'],
		['text/plain', 'text'],
		['text/richtext', 'rtf'],
		['text/richtext', 'rt'],
		['text/richtext', 'rtx'],
		['text/rtf', 'rtf'],
		['text/scriplet', 'wsc'],
		['text/sgml', 'sgm'],
		['text/sgml', 'sgml'],
		['text/tab-separated-values', 'tsv'],
		['text/uri-list', 'uris'],
		['text/uri-list', 'uni'],
		['text/uri-list', 'unis'],
		['text/uri-list', 'uri'],
		['text/vnd.abc', 'abc'],
		['text/vnd.fmi.flexstor', 'flx'],
		['text/vnd.rn-realtext', 'rt'],
		['text/vnd.wap.wml', 'wml'],
		['text/vnd.wap.wmlscript', 'wmls'],
		['text/webviewhtml', 'htt'],
		['text/x-asm', 'asm'],
		['text/x-asm', 's'],
		['text/x-audiosoft-intra', 'aip'],
		['text/x-c', 'c'],
		['text/x-c', 'cc'],
		['text/x-c', 'cpp'],
		['text/x-component', 'htc'],
		['text/x-fortran', 'f'],
		['text/x-fortran', 'f77'],
		['text/x-fortran', 'f90'],
		['text/x-fortran', 'for'],
		['text/x-h', 'h'],
		['text/x-h', 'hh'],
		['text/x-java-source', 'java'],
		['text/x-java-source', 'jav'],
		['text/x-la-asf', 'lsx'],
		['text/x-m', 'm'],
		['text/x-pascal', 'p'],
		['text/x-script', 'hlb'],
		['text/x-script.csh', 'csh'],
		['text/x-script.elisp', 'el'],
		['text/x-script.guile', 'scm'],
		['text/x-script.ksh', 'ksh'],
		['text/x-script.lisp', 'lsp'],
		['text/x-script.perl', 'pl'],
		['text/x-script.perl-module', 'pm'],
		['text/x-script.phyton', 'py'],
		['text/x-script.rexx', 'rexx'],
		['text/x-script.scheme', 'scm'],
		['text/x-script.sh', 'sh'],
		['text/x-script.tcl', 'tcl'],
		['text/x-script.tcsh', 'tcsh'],
		['text/x-script.zsh', 'zsh'],
		['text/x-server-parsed-html', 'shtml'],
		['text/x-server-parsed-html', 'ssi'],
		['text/x-setext', 'etx'],
		['text/x-sgml', 'sgml'],
		['text/x-sgml', 'sgm'],
		['text/x-speech', 'talk'],
		['text/x-speech', 'spc'],
		['text/x-uil', 'uil'],
		['text/x-uuencode', 'uue'],
		['text/x-uuencode', 'uu'],
		['text/x-vcalendar', 'vcs'],
		['text/xml', 'xml'],
		['video/animaflex', 'afl'],
		['video/avi', 'avi'],
		['video/avs-video', 'avs'],
		['video/dl', 'dl'],
		['video/fli', 'fli'],
		['video/gl', 'gl'],
		['video/mpeg', 'mpeg'],
		['video/mpeg', 'm1v'],
		['video/mpeg', 'm2v'],
		['video/mpeg', 'mp2'],
		['video/mpeg', 'mp3'],
		['video/mpeg', 'mpa'],
		['video/mpeg', 'mpe'],
		['video/mpeg', 'mpg'],
		['video/msvideo', 'avi'],
		['video/quicktime', 'mov'],
		['video/quicktime', 'moov'],
		['video/quicktime', 'qt'],
		['video/vdo', 'vdo'],
		['video/vivo', 'vivo'],
		['video/vivo', 'viv'],
		['video/vnd.rn-realvideo', 'rv'],
		['video/vnd.vivo', 'vivo'],
		['video/vnd.vivo', 'viv'],
		['video/vosaic', 'vos'],
		['video/x-amt-demorun', 'xdr'],
		['video/x-amt-showrun', 'xsr'],
		['video/x-atomic3d-feature', 'fmf'],
		['video/x-dl', 'dl'],
		['video/x-dv', 'dv'],
		['video/x-dv', 'dif'],
		['video/x-fli', 'fli'],
		['video/x-gl', 'gl'],
		['video/x-isvideo', 'isu'],
		['video/x-motion-jpeg', 'mjpg'],
		['video/x-mpeg', 'mp3'],
		['video/x-mpeg', 'mp2'],
		['video/x-mpeq2a', 'mp2'],
		['video/x-ms-asf', 'asf'],
		['video/x-ms-asf', 'asx'],
		['video/x-ms-asf-plugin', 'asx'],
		['video/x-msvideo', 'avi'],
		['video/x-qtc', 'qtc'],
		['video/x-scm', 'scm'],
		['video/x-sgi-movie', 'movie'],
		['video/x-sgi-movie', 'mv'],
		['windows/metafile', 'wmf'],
		['www/mime', 'mime'],
		['x-conference/x-cooltalk', 'ice'],
		['x-music/x-midi', 'midi'],
		['x-music/x-midi', 'mid'],
		['x-world/x-3dmf', '3dmf'],
		['x-world/x-3dmf', '3dm'],
		['x-world/x-3dmf', 'qd3'],
		['x-world/x-3dmf', 'qd3d'],
		['x-world/x-svr', 'svr'],
		['x-world/x-vrml', 'vrml'],
		['x-world/x-vrml', 'wrl'],
		['x-world/x-vrml', 'wrz'],
		['x-world/x-vrt', 'vrt'],
		['xgl/drawing', 'xgz'],
		['xgl/movie', 'xmz'],
		
		// New MS Office mime-types
		['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx'],
		['application/vnd.openxmlformats-officedocument.spreadsheetml.template', 'xltx'],
		['application/vnd.openxmlformats-officedocument.presentationml.template', 'potx'],
		['application/vnd.openxmlformats-officedocument.presentationml.slideshow', 'ppsx'],
		['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'pptx'],
		['application/vnd.openxmlformats-officedocument.presentationml.slide', 'sldx'],
		['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx'],
		['application/vnd.openxmlformats-officedocument.wordprocessingml.template', 'dotx'],
		['application/vnd.ms-excel.addin.macroEnabled.12', 'xlam'],
		['application/vnd.ms-excel.sheet.binary.macroEnabled.12', 'xlsb'],
	];
}

class FileException extends Exception {}
