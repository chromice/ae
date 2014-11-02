<?php if (!class_exists('ae')) exit;

#
# Copyright 2011-2014 Anton Muraviev <chromice@gmail.com>
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

ae::invoke('aeFile');

class aeFile
/*
	A thin wrapper that abstracts common file operations 
	mostly for the sake of exception safety.
	
		$file = ae::file('example.txt')
			->open('w')
			->write('This is a test.')
			->close();
	
	All methods throw `aeFileException` on failure.
*/
{
	protected $path;
	protected $name;
	protected $type;
	protected $meta;
	
	protected $file;
	protected $is_locked = false;
	
	public function __construct($path, $name = null, $meta = array())
	{
		if (is_array($name) && empty($meta))
		{
			$meta = $name;
			$name = null;
		}
		
		$this->path = $path;
		$this->meta = is_array($meta) ? $meta : array();
		$this->name = pathinfo(is_null($name) ? $path : $name, PATHINFO_FILENAME);
		$this->type = pathinfo(is_null($name) ? $path : $name, PATHINFO_EXTENSION);
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
			return $this->type;
		}
		
		$type = strtolower($this->type);
		$mimetype = $this->mimetype();
		
		$found = self::find_matching_types($mimetype, $type);
		$found = array_pop($found);
		
		if (isset($found[1]))
		{
			return $found[1];
		}
		
		if (!empty($type))
		{
			throw new aeFileException('No valid file type found for MIME: ' . $mimetype);
		}
		else
		{
			throw new aeFileException($type . ' is an invalid file type for MIME: ' . $mimetype);
		}
	}
	
	public static function find_matching_types($mimetype, $extension)
	{
		if (empty($mimetype) && empty($extension))
		{
			trigger_error('Cannot find matching type candidates: no mimetype or extension were specified.');
		}
		
		return array_filter(self::$types, function ($candidate) use ($mimetype, $extension) {
			return (empty($mimetype) || $candidate[0] === $mimetype)
				&& (empty($extension) || $candidate[1] === $extension);
		});
	}
	
	public function full_name($validate = true)
	{
		return $this->name() . '.' . $this->type($validate);
	}
	
	public function mimetype()
	{
		$info = new finfo(FILEINFO_MIME_TYPE);
		
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
			$this->type(false),
			$overwrite
		);
		
		if (!$this->is_uploaded() && false === @rename($this->path, $path)
		|| $this->is_uploaded() && false === @move_uploaded_file($this->path, $path))
		{
			throw new aeFileException('Failed to move file.');
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
			$this->type(false),
			$overwrite
		);
		
		if (false === @copy($this->path, $path))
		{
			throw new aeFileException('Failed to copy file.');
		}
		
		$this->path = $path;
		
		return $this;
	}
	
	public function delete()
	{
		$this->_cannot('delete file');
		
		if (false === @unlink($this->path))
		{
			throw new aeFileException('Failed to delete file.');
		}
		
		return $this;
	}
	
	protected static function _destination($destination, $name, $type, $overwrite)
	{
		// FIXME: Destination may have subdirectories missing.
		if (!is_dir($destination) && is_dir(pathinfo($destination, PATHINFO_DIRNAME)))
		{
			$name = pathinfo($destination, PATHINFO_FILENAME);
			$type = pathinfo($destination, PATHINFO_EXTENSION);
			
			$destination = pathinfo($destination, PATHINFO_DIRNAME);
		}
		
		$i = 0;
		$path = rtrim($destination, '/') . '/' . $name . '.' . $type;
		
		while (file_exists($path) && !$overwrite)
		{
			$path = rtrim($destination, '/') . '/' . $name . ' ' . ++$i . '.' . $type;
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
			throw new aeFileException('File is already opened.');
		}
		
		$this->file = is_resource($context)
			? fopen($this->path, $mode, $use_include_path, $context)
			: fopen($this->path, $mode, $use_include_path);
		
		if (false === $this->file)
		{
			throw new aeFileException('Failed to open file.');
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
			throw new aeFileException('Failed to close file.');
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
			throw new aeFileException('Failed to lock file.');
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
			throw new aeFileException('Failed to unlock file.');
		}
		
		$this->is_locked = false;
		
		return $this;
	}
	
	public function truncate($size = 0)
	{
		$this->_can('truncate file');
		
		if (false === ftruncate($this->file, $size))
		{
			throw new aeFileException('Failed to truncate file.');
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
			throw new aeFileException('Failed to write to file.');
		}
		
		return $this;
	}
	
	public function read($length)
	{
		$this->_can('read from file');
		
		if (false === fread($this->file, $length))
		{
			throw new aeFileException('Failed to write to file.');
		}

		return $this;
	}
	
	public function seek($offset, $whence = SEEK_SET)
	{
		$this->_can('seek the position');
		
		if (-1 === fseek($this->file, $offset, $whence))
		{
			throw new aeFileException('Failed to seek the position.');
		}
		
		return $this;
	}
	
	public function tell()
	{
		$this->_can('tell the position');
		
		if (flase === ($offset = ftell($this->file)))
		{
			throw new aeFileException('Failed to return the offset.');
		}
		
		return $offset;
	}
	
	protected function _can($intent)
	{
		if (!is_resource($this->file))
		{
			throw new aeFileException('Cannot ' . $intent . '. File is not opened.');
		}
	}
	protected function _cannot($intent)
	{
		if (is_resource($this->file))
		{
			throw new aeFileException('Cannot ' . $intent . '. File is opened.');
		}
	}
	
	protected static $types = array(
		array('application/x-bytecode.python', 'pyc'),
		array('application/acad', 'dwg'),
		array('application/arj', 'arj'),
		array('application/base64', 'mm'),
		array('application/base64', 'mme'),
		array('application/binhex', 'hqx'),
		array('application/binhex4', 'hqx'),
		array('application/book', 'book'),
		array('application/book', 'boo'),
		array('application/cdf', 'cdf'),
		array('application/clariscad', 'ccad'),
		array('application/commonground', 'dp'),
		array('application/drafting', 'drw'),
		array('application/dsptype', 'tsp'),
		array('application/dxf', 'dxf'),
		array('application/ecmascript', 'js'),
		array('application/envoy', 'evy'),
		array('application/excel', 'xls'),
		array('application/excel', 'xl'),
		array('application/excel', 'xla'),
		array('application/excel', 'xlb'),
		array('application/excel', 'xlc'),
		array('application/excel', 'xld'),
		array('application/excel', 'xlk'),
		array('application/excel', 'xll'),
		array('application/excel', 'xlm'),
		array('application/excel', 'xlt'),
		array('application/excel', 'xlv'),
		array('application/excel', 'xlw'),
		array('application/fractals', 'fif'),
		array('application/freeloader', 'frl'),
		array('application/futuresplash', 'spl'),
		array('application/gnutar', 'tgz'),
		array('application/groupwise', 'vew'),
		array('application/hlp', 'hlp'),
		array('application/hta', 'hta'),
		array('application/i-deas', 'unv'),
		array('application/iges', 'iges'),
		array('application/iges', 'igs'),
		array('application/inf', 'inf'),
		array('application/java', 'class'),
		array('application/java-byte-code', 'class'),
		array('application/javascript', 'js'),
		array('application/lha', 'lha'),
		array('application/lzx', 'lzx'),
		array('application/mac-binary', 'bin'),
		array('application/mac-binhex', 'hqx'),
		array('application/mac-binhex40', 'hqx'),
		array('application/mac-compactpro', 'cpt'),
		array('application/macbinary', 'bin'),
		array('application/marc', 'mrc'),
		array('application/mbedlet', 'mbd'),
		array('application/mcad', 'mcd'),
		array('application/mime', 'aps'),
		array('application/mspowerpoint', 'pot'),
		array('application/mspowerpoint', 'pps'),
		array('application/mspowerpoint', 'ppt'),
		array('application/mspowerpoint', 'ppz'),
		array('application/msword', 'doc'),
		array('application/msword', 'dot'),
		array('application/msword', 'w6w'),
		array('application/msword', 'wiz'),
		array('application/msword', 'word'),
		array('application/mswrite', 'wri'),
		array('application/netmc', 'mcp'),
		array('application/octet-stream', 'a'),
		array('application/octet-stream', 'arc'),
		array('application/octet-stream', 'arj'),
		array('application/octet-stream', 'bin'),
		array('application/octet-stream', 'com'),
		array('application/octet-stream', 'dump'),
		array('application/octet-stream', 'exe'),
		array('application/octet-stream', 'lha'),
		array('application/octet-stream', 'lhx'),
		array('application/octet-stream', 'lzh'),
		array('application/octet-stream', 'lzx'),
		array('application/octet-stream', 'o'),
		array('application/octet-stream', 'psd'),
		array('application/octet-stream', 'saveme'),
		array('application/octet-stream', 'uu'),
		array('application/octet-stream', 'zoo'),
		array('application/oda', 'oda'),
		array('application/pdf', 'pdf'),
		array('application/pkcs-12', 'p12'),
		array('application/pkcs-crl', 'crl'),
		array('application/pkcs10', 'p10'),
		array('application/pkcs7-mime', 'p7m'),
		array('application/pkcs7-mime', 'p7c'),
		array('application/pkcs7-signature', 'p7s'),
		array('application/pkix-cert', 'cer'),
		array('application/pkix-cert', 'crt'),
		array('application/pkix-crl', 'crl'),
		array('application/plain', 'text'),
		array('application/postscript', 'ps'),
		array('application/postscript', 'ai'),
		array('application/postscript', 'eps'),
		array('application/powerpoint', 'ppt'),
		array('application/pro_eng', 'part'),
		array('application/pro_eng', 'prt'),
		array('application/ringing-tones', 'rng'),
		array('application/rtf', 'rtf'),
		array('application/rtf', 'rtx'),
		array('application/sdp', 'sdp'),
		array('application/sea', 'sea'),
		array('application/set', 'set'),
		array('application/sla', 'stl'),
		array('application/smil', 'smi'),
		array('application/smil', 'smil'),
		array('application/solids', 'sol'),
		array('application/sounder', 'sdr'),
		array('application/step', 'step'),
		array('application/step', 'stp'),
		array('application/streamingmedia', 'ssm'),
		array('application/toolbook', 'tbk'),
		array('application/vda', 'vda'),
		array('application/vnd.fdf', 'fdf'),
		array('application/vnd.hp-hpgl', 'hgl'),
		array('application/vnd.hp-hpgl', 'hpg'),
		array('application/vnd.hp-hpgl', 'hpgl'),
		array('application/vnd.hp-pcl', 'pcl'),
		array('application/vnd.ms-excel', 'xls'),
		array('application/vnd.ms-excel', 'xlb'),
		array('application/vnd.ms-excel', 'xlc'),
		array('application/vnd.ms-excel', 'xll'),
		array('application/vnd.ms-excel', 'xlm'),
		array('application/vnd.ms-excel', 'xlw'),
		array('application/vnd.ms-pki.certstore', 'sst'),
		array('application/vnd.ms-pki.pko', 'pko'),
		array('application/vnd.ms-pki.seccat', 'cat'),
		array('application/vnd.ms-pki.stl', 'stl'),
		array('application/vnd.ms-powerpoint', 'ppt'),
		array('application/vnd.ms-powerpoint', 'pot'),
		array('application/vnd.ms-powerpoint', 'ppa'),
		array('application/vnd.ms-powerpoint', 'pps'),
		array('application/vnd.ms-powerpoint', 'pwz'),
		array('application/vnd.ms-project', 'mpp'),
		array('application/vnd.nokia.configuration-message', 'ncm'),
		array('application/vnd.nokia.ringing-tone', 'rng'),
		array('application/vnd.rn-realmedia', 'rm'),
		array('application/vnd.rn-realplayer', 'rnx'),
		array('application/vnd.wap.wmlc', 'wmlc'),
		array('application/vnd.wap.wmlscriptc', 'wmlsc'),
		array('application/vnd.xara', 'web'),
		array('application/vocaltec-media-desc', 'vmd'),
		array('application/vocaltec-media-file', 'vmf'),
		array('application/wordperfect', 'wp'),
		array('application/wordperfect', 'wp5'),
		array('application/wordperfect', 'wp6'),
		array('application/wordperfect', 'wpd'),
		array('application/wordperfect6.0', 'w60'),
		array('application/wordperfect6.0', 'wp5'),
		array('application/wordperfect6.1', 'w61'),
		array('application/x-123', 'wk1'),
		array('application/x-aim', 'aim'),
		array('application/x-authorware-bin', 'aab'),
		array('application/x-authorware-map', 'aam'),
		array('application/x-authorware-seg', 'aas'),
		array('application/x-bcpio', 'bcpio'),
		array('application/x-binary', 'bin'),
		array('application/x-binhex40', 'hqx'),
		array('application/x-bsh', 'bsh'),
		array('application/x-bsh', 'sh'),
		array('application/x-bsh', 'shar'),
		array('application/x-bytecode.elisp (compiled elisp)', 'elc'),
		array('application/x-bzip', 'bz'),
		array('application/x-bzip2', 'bz2'),
		array('application/x-bzip2', 'boz'),
		array('application/x-cdf', 'cdf'),
		array('application/x-cdlink', 'vcd'),
		array('application/x-chat', 'cha'),
		array('application/x-chat', 'chat'),
		array('application/x-cmu-raster', 'ras'),
		array('application/x-cocoa', 'cco'),
		array('application/x-compactpro', 'cpt'),
		array('application/x-compress', 'z'),
		array('application/x-compressed', 'z'),
		array('application/x-compressed', 'gz'),
		array('application/x-compressed', 'tgz'),
		array('application/x-compressed', 'zip'),
		array('application/x-conference', 'nsc'),
		array('application/x-cpio', 'cpio'),
		array('application/x-cpt', 'cpt'),
		array('application/x-csh', 'csh'),
		array('application/x-deepv', 'deepv'),
		array('application/x-director', 'dir'),
		array('application/x-director', 'dcr'),
		array('application/x-director', 'dxr'),
		array('application/x-dvi', 'dvi'),
		array('application/x-elc', 'elc'),
		array('application/x-envoy', 'env'),
		array('application/x-envoy', 'evy'),
		array('application/x-esrehber', 'es'),
		array('application/x-excel', 'xls'),
		array('application/x-excel', 'xla'),
		array('application/x-excel', 'xlb'),
		array('application/x-excel', 'xlc'),
		array('application/x-excel', 'xld'),
		array('application/x-excel', 'xlk'),
		array('application/x-excel', 'xll'),
		array('application/x-excel', 'xlm'),
		array('application/x-excel', 'xlt'),
		array('application/x-excel', 'xlv'),
		array('application/x-excel', 'xlw'),
		array('application/x-frame', 'mif'),
		array('application/x-freelance', 'pre'),
		array('application/x-gsp', 'gsp'),
		array('application/x-gss', 'gss'),
		array('application/x-gtar', 'gtar'),
		array('application/x-gzip', 'gz'),
		array('application/x-gzip', 'gzip'),
		array('application/x-hdf', 'hdf'),
		array('application/x-helpfile', 'help'),
		array('application/x-helpfile', 'hlp'),
		array('application/x-httpd-imap', 'imap'),
		array('application/x-ima', 'ima'),
		array('application/x-internett-signup', 'ins'),
		array('application/x-inventor', 'iv'),
		array('application/x-ip2', 'ip'),
		array('application/x-java-class', 'class'),
		array('application/x-java-commerce', 'jcm'),
		array('application/x-javascript', 'js'),
		array('application/x-koan', 'skd'),
		array('application/x-koan', 'skm'),
		array('application/x-koan', 'skp'),
		array('application/x-koan', 'skt'),
		array('application/x-ksh', 'ksh'),
		array('application/x-latex', 'latex'),
		array('application/x-latex', 'ltx'),
		array('application/x-lha', 'lha'),
		array('application/x-lisp', 'lsp'),
		array('application/x-livescreen', 'ivy'),
		array('application/x-lotus', 'wq1'),
		array('application/x-lotusscreencam', 'scm'),
		array('application/x-lzh', 'lzh'),
		array('application/x-lzx', 'lzx'),
		array('application/x-mac-binhex40', 'hqx'),
		array('application/x-macbinary', 'bin'),
		array('application/x-magic-cap-package-1.0', 'mc$'),
		array('application/x-mathcad', 'mcd'),
		array('application/x-meme', 'mm'),
		array('application/x-midi', 'midi'),
		array('application/x-midi', 'mid'),
		array('application/x-mif', 'mif'),
		array('application/x-mix-transfer', 'nix'),
		array('application/x-mplayer2', 'asx'),
		array('application/x-msexcel', 'xla'),
		array('application/x-msexcel', 'xls'),
		array('application/x-msexcel', 'xlw'),
		array('application/x-mspowerpoint', 'ppt'),
		array('application/x-navi-animation', 'ani'),
		array('application/x-navidoc', 'nvd'),
		array('application/x-navimap', 'map'),
		array('application/x-navistyle', 'stl'),
		array('application/x-netcdf', 'cdf'),
		array('application/x-netcdf', 'nc'),
		array('application/x-newton-compatible-pkg', 'pkg'),
		array('application/x-nokia-9000-communicator-add-on-software', 'aos'),
		array('application/x-omc', 'omc'),
		array('application/x-omcdatamaker', 'omcd'),
		array('application/x-omcregerator', 'omcr'),
		array('application/x-pagemaker', 'pm5'),
		array('application/x-pagemaker', 'pm4'),
		array('application/x-pcl', 'pcl'),
		array('application/x-pixclscript', 'plx'),
		array('application/x-pkcs10', 'p10'),
		array('application/x-pkcs12', 'p12'),
		array('application/x-pkcs7-certificates', 'spc'),
		array('application/x-pkcs7-certreqresp', 'p7r'),
		array('application/x-pkcs7-mime', 'p7c'),
		array('application/x-pkcs7-mime', 'p7m'),
		array('application/x-pkcs7-signature', 'p7a'),
		array('application/x-pointplus', 'css'),
		array('application/x-portable-anymap', 'pnm'),
		array('application/x-project', 'mpc'),
		array('application/x-project', 'mpt'),
		array('application/x-project', 'mpv'),
		array('application/x-project', 'mpx'),
		array('application/x-qpro', 'wb1'),
		array('application/x-rtf', 'rtf'),
		array('application/x-sdp', 'sdp'),
		array('application/x-sea', 'sea'),
		array('application/x-seelogo', 'sl'),
		array('application/x-sh', 'sh'),
		array('application/x-shar', 'shar'),
		array('application/x-shar', 'sh'),
		array('application/x-shockwave-flash', 'swf'),
		array('application/x-sit', 'sit'),
		array('application/x-sprite', 'sprite'),
		array('application/x-sprite', 'spr'),
		array('application/x-stuffit', 'sit'),
		array('application/x-sv4cpio', 'sv4cpio'),
		array('application/x-sv4crc', 'sv4crc'),
		array('application/x-tar', 'tar'),
		array('application/x-tbook', 'sbk'),
		array('application/x-tbook', 'tbk'),
		array('application/x-tcl', 'tcl'),
		array('application/x-tex', 'tex'),
		array('application/x-texinfo', 'texinfo'),
		array('application/x-texinfo', 'texi'),
		array('application/x-troff', 'roff'),
		array('application/x-troff', 't'),
		array('application/x-troff', 'tr'),
		array('application/x-troff-man', 'man'),
		array('application/x-troff-me', 'me'),
		array('application/x-troff-ms', 'ms'),
		array('application/x-troff-msvideo', 'avi'),
		array('application/x-ustar', 'ustar'),
		array('application/x-visio', 'vsd'),
		array('application/x-visio', 'vst'),
		array('application/x-visio', 'vsw'),
		array('application/x-vnd.audioexplosion.mzz', 'mzz'),
		array('application/x-vnd.ls-xpix', 'xpix'),
		array('application/x-vrml', 'vrml'),
		array('application/x-wais-source', 'wsrc'),
		array('application/x-wais-source', 'src'),
		array('application/x-winhelp', 'hlp'),
		array('application/x-wintalk', 'wtk'),
		array('application/x-world', 'wrl'),
		array('application/x-world', 'svr'),
		array('application/x-wpwin', 'wpd'),
		array('application/x-wri', 'wri'),
		array('application/x-x509-ca-cert', 'cer'),
		array('application/x-x509-ca-cert', 'crt'),
		array('application/x-x509-ca-cert', 'der'),
		array('application/x-x509-user-cert', 'crt'),
		array('application/x-zip-compressed', 'zip'),
		array('application/xml', 'xml'),
		array('application/zip', 'zip'),
		array('audio/aiff', 'aiff'),
		array('audio/aiff', 'aifc'),
		array('audio/aiff', 'aif'),
		array('audio/basic', 'au'),
		array('audio/basic', 'snd'),
		array('audio/it', 'it'),
		array('audio/make', 'funk'),
		array('audio/make', 'my'),
		array('audio/make', 'pfunk'),
		array('audio/make.my.funk', 'pfunk'),
		array('audio/mid', 'rmi'),
		array('audio/midi', 'midi'),
		array('audio/midi', 'mid'),
		array('audio/midi', 'kar'),
		array('audio/mod', 'mod'),
		array('audio/mpeg', 'm2a'),
		array('audio/mpeg', 'mp2'),
		array('audio/mpeg', 'mpa'),
		array('audio/mpeg', 'mpg'),
		array('audio/mpeg', 'mpga'),
		array('audio/mpeg3', 'mp3'),
		array('audio/nspaudio', 'la'),
		array('audio/nspaudio', 'lma'),
		array('audio/s3m', 's3m'),
		array('audio/tsp-audio', 'tsi'),
		array('audio/tsplayer', 'tsp'),
		array('audio/vnd.qcelp', 'qcp'),
		array('audio/voc', 'voc'),
		array('audio/voxware', 'vox'),
		array('audio/wav', 'wav'),
		array('audio/x-adpcm', 'snd'),
		array('audio/x-aiff', 'aiff'),
		array('audio/x-aiff', 'aif'),
		array('audio/x-aiff', 'aifc'),
		array('audio/x-au', 'au'),
		array('audio/x-gsm', 'gsm'),
		array('audio/x-gsm', 'gsd'),
		array('audio/x-jam', 'jam'),
		array('audio/x-liveaudio', 'lam'),
		array('audio/x-mid', 'midi'),
		array('audio/x-mid', 'mid'),
		array('audio/x-midi', 'midi'),
		array('audio/x-midi', 'mid'),
		array('audio/x-mod', 'mod'),
		array('audio/x-mpeg', 'mp2'),
		array('audio/x-mpeg-3', 'mp3'),
		array('audio/x-mpequrl', 'm3u'),
		array('audio/x-nspaudio', 'la'),
		array('audio/x-nspaudio', 'lma'),
		array('audio/x-pn-realaudio', 'ra'),
		array('audio/x-pn-realaudio', 'ram'),
		array('audio/x-pn-realaudio', 'rm'),
		array('audio/x-pn-realaudio', 'rmm'),
		array('audio/x-pn-realaudio', 'rmp'),
		array('audio/x-pn-realaudio-plugin', 'ra'),
		array('audio/x-pn-realaudio-plugin', 'rmp'),
		array('audio/x-pn-realaudio-plugin', 'rpm'),
		array('audio/x-psid', 'sid'),
		array('audio/x-realaudio', 'ra'),
		array('audio/x-twinvq', 'vqf'),
		array('audio/x-twinvq-plugin', 'vqe'),
		array('audio/x-twinvq-plugin', 'vql'),
		array('audio/x-vnd.audioexplosion.mjuicemediafile', 'mjf'),
		array('audio/x-voc', 'voc'),
		array('audio/x-wav', 'wav'),
		array('audio/xm', 'xm'),
		array('chemical/x-pdb', 'pdb'),
		array('chemical/x-pdb', 'xyz'),
		array('drawing/x-dwf (old)', 'dwf'),
		array('i-world/i-vrml', 'ivr'),
		array('image/bmp', 'bmp'),
		array('image/bmp', 'bm'),
		array('image/cmu-raster', 'ras'),
		array('image/cmu-raster', 'rast'),
		array('image/fif', 'fif'),
		array('image/florian', 'flo'),
		array('image/florian', 'turbot'),
		array('image/g3fax', 'g3'),
		array('image/gif', 'gif'),
		array('image/ief', 'ief'),
		array('image/ief', 'iefs'),
		array('image/jpeg', 'jpeg'),
		array('image/jpeg', 'jpg'),
		array('image/jpeg', 'jfif'),
		array('image/jpeg', 'jfif-tbnl'),
		array('image/jpeg', 'jpe'),
		array('image/jutvision', 'jut'),
		array('image/naplps', 'naplps'),
		array('image/naplps', 'nap'),
		array('image/pict', 'pict'),
		array('image/pict', 'pic'),
		array('image/pjpeg', 'jpeg'),
		array('image/pjpeg', 'jfif'),
		array('image/pjpeg', 'jpe'),
		array('image/pjpeg', 'jpg'),
		array('image/png', 'png'),
		array('image/png', 'x-png'),
		array('image/tiff', 'tiff'),
		array('image/tiff', 'tif'),
		array('image/vasa', 'mcf'),
		array('image/vnd.dwg', 'dwg'),
		array('image/vnd.dwg', 'dxf'),
		array('image/vnd.dwg', 'svf'),
		array('image/vnd.fpx', 'fpx'),
		array('image/vnd.net-fpx', 'fpx'),
		array('image/vnd.rn-realflash', 'rf'),
		array('image/vnd.rn-realpix', 'rp'),
		array('image/vnd.wap.wbmp', 'wbmp'),
		array('image/vnd.xiff', 'xif'),
		array('image/x-cmu-raster', 'ras'),
		array('image/x-dwg', 'dwg'),
		array('image/x-dwg', 'dxf'),
		array('image/x-dwg', 'svf'),
		array('image/x-icon', 'ico'),
		array('image/x-jg', 'art'),
		array('image/x-jps', 'jps'),
		array('image/x-niff', 'niff'),
		array('image/x-niff', 'nif'),
		array('image/x-pcx', 'pcx'),
		array('image/x-pict', 'pct'),
		array('image/x-portable-anymap', 'pnm'),
		array('image/x-portable-bitmap', 'pbm'),
		array('image/x-portable-graymap', 'pgm'),
		array('image/x-portable-greymap', 'pgm'),
		array('image/x-portable-pixmap', 'ppm'),
		array('image/x-quicktime', 'qif'),
		array('image/x-quicktime', 'qti'),
		array('image/x-quicktime', 'qtif'),
		array('image/x-rgb', 'rgb'),
		array('image/x-tiff', 'tiff'),
		array('image/x-tiff', 'tif'),
		array('image/x-windows-bmp', 'bmp'),
		array('image/x-xbitmap', 'xbm'),
		array('image/x-xbm', 'xbm'),
		array('image/x-xpixmap', 'xpm'),
		array('image/x-xpixmap', 'pm'),
		array('image/x-xwd', 'xwd'),
		array('image/x-xwindowdump', 'xwd'),
		array('image/xbm', 'xbm'),
		array('image/xpm', 'xpm'),
		array('message/rfc822', 'mht'),
		array('message/rfc822', 'mhtml'),
		array('message/rfc822', 'mime'),
		array('model/iges', 'iges'),
		array('model/iges', 'igs'),
		array('model/vnd.dwf', 'dwf'),
		array('model/vrml', 'vrml'),
		array('model/vrml', 'wrl'),
		array('model/vrml', 'wrz'),
		array('model/x-pov', 'pov'),
		array('multipart/x-gzip', 'gzip'),
		array('multipart/x-ustar', 'ustar'),
		array('multipart/x-zip', 'zip'),
		array('music/crescendo', 'mid'),
		array('music/crescendo', 'midi'),
		array('music/x-karaoke', 'kar'),
		array('paleovu/x-pv', 'pvu'),
		array('text/asp', 'asp'),
		array('text/css', 'css'),
		array('text/ecmascript', 'js'),
		array('text/html', 'acgi'),
		array('text/html', 'htm'),
		array('text/html', 'html'),
		array('text/html', 'htmls'),
		array('text/html', 'htx'),
		array('text/html', 'shtml'),
		array('text/javascript', 'js'),
		array('text/mcf', 'mcf'),
		array('text/pascal', 'pas'),
		array('text/plain', 'txt'),
		array('text/plain', 'c'),
		array('text/plain', 'c++'),
		array('text/plain', 'cc'),
		array('text/plain', 'com'),
		array('text/plain', 'conf'),
		array('text/plain', 'cxx'),
		array('text/plain', 'def'),
		array('text/plain', 'f'),
		array('text/plain', 'f90'),
		array('text/plain', 'for'),
		array('text/plain', 'g'),
		array('text/plain', 'h'),
		array('text/plain', 'hh'),
		array('text/plain', 'idc'),
		array('text/plain', 'jav'),
		array('text/plain', 'java'),
		array('text/plain', 'list'),
		array('text/plain', 'log'),
		array('text/plain', 'lst'),
		array('text/plain', 'm'),
		array('text/plain', 'mar'),
		array('text/plain', 'pl'),
		array('text/plain', 'sdml'),
		array('text/plain', 'text'),
		array('text/richtext', 'rtf'),
		array('text/richtext', 'rt'),
		array('text/richtext', 'rtx'),
		array('text/scriplet', 'wsc'),
		array('text/sgml', 'sgm'),
		array('text/sgml', 'sgml'),
		array('text/tab-separated-values', 'tsv'),
		array('text/uri-list', 'uris'),
		array('text/uri-list', 'uni'),
		array('text/uri-list', 'unis'),
		array('text/uri-list', 'uri'),
		array('text/vnd.abc', 'abc'),
		array('text/vnd.fmi.flexstor', 'flx'),
		array('text/vnd.rn-realtext', 'rt'),
		array('text/vnd.wap.wml', 'wml'),
		array('text/vnd.wap.wmlscript', 'wmls'),
		array('text/webviewhtml', 'htt'),
		array('text/x-asm', 'asm'),
		array('text/x-asm', 's'),
		array('text/x-audiosoft-intra', 'aip'),
		array('text/x-c', 'c'),
		array('text/x-c', 'cc'),
		array('text/x-c', 'cpp'),
		array('text/x-component', 'htc'),
		array('text/x-fortran', 'f'),
		array('text/x-fortran', 'f77'),
		array('text/x-fortran', 'f90'),
		array('text/x-fortran', 'for'),
		array('text/x-h', 'h'),
		array('text/x-h', 'hh'),
		array('text/x-java-source', 'java'),
		array('text/x-java-source', 'jav'),
		array('text/x-la-asf', 'lsx'),
		array('text/x-m', 'm'),
		array('text/x-pascal', 'p'),
		array('text/x-script', 'hlb'),
		array('text/x-script.csh', 'csh'),
		array('text/x-script.elisp', 'el'),
		array('text/x-script.guile', 'scm'),
		array('text/x-script.ksh', 'ksh'),
		array('text/x-script.lisp', 'lsp'),
		array('text/x-script.perl', 'pl'),
		array('text/x-script.perl-module', 'pm'),
		array('text/x-script.phyton', 'py'),
		array('text/x-script.rexx', 'rexx'),
		array('text/x-script.scheme', 'scm'),
		array('text/x-script.sh', 'sh'),
		array('text/x-script.tcl', 'tcl'),
		array('text/x-script.tcsh', 'tcsh'),
		array('text/x-script.zsh', 'zsh'),
		array('text/x-server-parsed-html', 'shtml'),
		array('text/x-server-parsed-html', 'ssi'),
		array('text/x-setext', 'etx'),
		array('text/x-sgml', 'sgml'),
		array('text/x-sgml', 'sgm'),
		array('text/x-speech', 'talk'),
		array('text/x-speech', 'spc'),
		array('text/x-uil', 'uil'),
		array('text/x-uuencode', 'uue'),
		array('text/x-uuencode', 'uu'),
		array('text/x-vcalendar', 'vcs'),
		array('text/xml', 'xml'),
		array('video/animaflex', 'afl'),
		array('video/avi', 'avi'),
		array('video/avs-video', 'avs'),
		array('video/dl', 'dl'),
		array('video/fli', 'fli'),
		array('video/gl', 'gl'),
		array('video/mpeg', 'mpeg'),
		array('video/mpeg', 'm1v'),
		array('video/mpeg', 'm2v'),
		array('video/mpeg', 'mp2'),
		array('video/mpeg', 'mp3'),
		array('video/mpeg', 'mpa'),
		array('video/mpeg', 'mpe'),
		array('video/mpeg', 'mpg'),
		array('video/msvideo', 'avi'),
		array('video/quicktime', 'mov'),
		array('video/quicktime', 'moov'),
		array('video/quicktime', 'qt'),
		array('video/vdo', 'vdo'),
		array('video/vivo', 'vivo'),
		array('video/vivo', 'viv'),
		array('video/vnd.rn-realvideo', 'rv'),
		array('video/vnd.vivo', 'vivo'),
		array('video/vnd.vivo', 'viv'),
		array('video/vosaic', 'vos'),
		array('video/x-amt-demorun', 'xdr'),
		array('video/x-amt-showrun', 'xsr'),
		array('video/x-atomic3d-feature', 'fmf'),
		array('video/x-dl', 'dl'),
		array('video/x-dv', 'dv'),
		array('video/x-dv', 'dif'),
		array('video/x-fli', 'fli'),
		array('video/x-gl', 'gl'),
		array('video/x-isvideo', 'isu'),
		array('video/x-motion-jpeg', 'mjpg'),
		array('video/x-mpeg', 'mp3'),
		array('video/x-mpeg', 'mp2'),
		array('video/x-mpeq2a', 'mp2'),
		array('video/x-ms-asf', 'asf'),
		array('video/x-ms-asf', 'asx'),
		array('video/x-ms-asf-plugin', 'asx'),
		array('video/x-msvideo', 'avi'),
		array('video/x-qtc', 'qtc'),
		array('video/x-scm', 'scm'),
		array('video/x-sgi-movie', 'movie'),
		array('video/x-sgi-movie', 'mv'),
		array('windows/metafile', 'wmf'),
		array('www/mime', 'mime'),
		array('x-conference/x-cooltalk', 'ice'),
		array('x-music/x-midi', 'midi'),
		array('x-music/x-midi', 'mid'),
		array('x-world/x-3dmf', '3dmf'),
		array('x-world/x-3dmf', '3dm'),
		array('x-world/x-3dmf', 'qd3'),
		array('x-world/x-3dmf', 'qd3d'),
		array('x-world/x-svr', 'svr'),
		array('x-world/x-vrml', 'vrml'),
		array('x-world/x-vrml', 'wrl'),
		array('x-world/x-vrml', 'wrz'),
		array('x-world/x-vrt', 'vrt'),
		array('xgl/drawing', 'xgz'),
		array('xgl/movie', 'xmz'),
		
		// New MS Office mime-types
		array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xlsx'),
		array('application/vnd.openxmlformats-officedocument.spreadsheetml.template', 'xltx'),
		array('application/vnd.openxmlformats-officedocument.presentationml.template', 'potx'),
		array('application/vnd.openxmlformats-officedocument.presentationml.slideshow', 'ppsx'),
		array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'pptx'),
		array('application/vnd.openxmlformats-officedocument.presentationml.slide', 'sldx'),
		array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx'),
		array('application/vnd.openxmlformats-officedocument.wordprocessingml.template', 'dotx'),
		array('application/vnd.ms-excel.addin.macroEnabled.12', 'xlam'),
		array('application/vnd.ms-excel.sheet.binary.macroEnabled.12', 'xlsb'),
	);
}

class aeFileException extends aeException {}
