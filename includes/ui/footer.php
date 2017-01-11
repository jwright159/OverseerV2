		<footer class="footer">
			<div class="container">
				<p class="pull-left text-muted">
					<?php
					$commitHash = trim(exec('git log --pretty="%h" -n1 HEAD'));
					echo "<b>Overseer v2.5-dev</b> (<code>$commitHash</code>)";
					?>
				</p>

				<p class="text-muted pull-right">
					<?php
					$renderTime = (microtime(true) - $renderStartTime); // microtime is in μs
					printf("Page render time: <b>%.3f ms</b>", $renderTime*1000); // convert to ms, show three decimal places
					?>
				</p>
			</div>
		</footer>
	</body>
</html>
