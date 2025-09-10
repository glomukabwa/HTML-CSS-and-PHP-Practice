#!/usr/bin/env python3
"""
recover_primes.py

Usage:
    python recover_primes.py

This script:
 - Tries trial division up to a small bound
 - Tries Pollard Rho (several random seeds)
 - Tries Pollard p-1 with increasing B1 bounds
 - If available, can call external tools gmp-ecm or yafu (commented/instructions)
 - Checks if c1/c2 are small perfect e-th powers (no modular reduction case)
 - If p,q are found, demonstrates how to recover x,y and verify relationships.

Edit the N, e1, e2, c1, c2 variables with your data (already inlined below).
"""
import math, random, sys, subprocess, shutil
from math import isqrt
from fractions import Fraction

# ---------- Paste values here ----------
N = int("14905562257842714057932724129575002825405393502650869767115942606408600343380327866258982402447992564988466588305174271674657844352454543958847568190372446723549627752274442789184236490768272313187410077124234699854724907039770193680822495470532218905083459730998003622926152590597710213127952141056029516116785229504645179830037937222022291571738973603920664929150436463632305664687903244972880062028301085749434688159905768052041207513149370212313943117665914802379158613359049957688563885391972151218676545972118494969247440489763431359679770422939441710783575668679693678435669541781490217731619224470152467768073")
e1 = int("12886657667389660800780796462970504910193928992888518978200029826975978624718627799215564700096007849924866627154987365059524315097631111242449314835868137")
e2 = int("12110586673991788415780355139635579057920926864887110308343229256046868242179445444897790171351302575188607117081580121488253540215781625598048021161675697")
c1 = int("14010729418703228234352465883041270611113735889838753433295478495763409056136734155612156934673988344882629541204985909650433819205298939877837314145082403528055884752079219150739849992921393509593620449489882380176216648401057401569934043087087362272303101549800941212057354903559653373299153430753882035233354304783275982332995766778499425529570008008029401325668301144188970480975565215953953985078281395545902102245755862663621187438677596628109967066418993851632543137353041712721919291521767262678140115188735994447949166616101182806820741928292882642234238450207472914232596747755261325098225968268926580993051")
c2 = int("14386997138637978860748278986945098648507142864584111124202580365103793165811666987664851210230009375267398957979494066880296418013345006977654742303441030008490816239306394492168516278328851513359596253775965916326353050138738183351643338294802012193721879700283088378587949921991198231956871429805847767716137817313612304833733918657887480468724409753522369325138502059408241232155633806496752350562284794715321835226991147547651155287812485862794935695241612676255374480132722940682140395725089329445356434489384831036205387293760789976615210310436732813848937666608611803196199865435145094486231635966885932646519")
# --------------------------------------

def is_probable_prime(n):
    # fallback to pow-based Miller-Rabin deterministic bases for 64-bit? n is big so rely on sympy if available
    try:
        import sympy
        return sympy.isprime(n)
    except Exception:
        # Simple Miller-Rabin with a few bases (not deterministic for huge n) - best-effort only
        def miller_rabin(n, bases=[2,3,5,7,11,13,17,19,23]):
            if n < 2: return False
            d = n-1; s=0
            while d % 2 == 0:
                d//=2; s+=1
            for a in bases:
                if a >= n: continue
                x = pow(a, d, n)
                if x == 1 or x == n-1: continue
                composite = True
                for _ in range(s-1):
                    x = pow(x,2,n)
                    if x == n-1:
                        composite = False; break
                if composite: return False
            return True
        return miller_rabin(n)

# integer e-th root check (returns r if r**e == v else None). Uses binary search.
def integer_root(v, e):
    if v < 0:
        return None
    lo = 0
    hi = 1 << ((v.bit_length() // e) + 2)
    while lo <= hi:
        mid = (lo + hi) // 2
        p = pow(mid, e)
        if p == v:
            return mid
        if p < v:
            lo = mid + 1
        else:
            hi = mid - 1
    return None

# ---------- cheap trial division ----------
def trial_division(n, limit=100000):
    from sympy import primerange
    for p in primerange(2, limit+1):
        if n % p == 0:
            return p
    return None

# ---------- Pollard Rho ----------
def pollard_rho(n, tries=8, max_iter=200000):
    if n % 2 == 0:
        return 2
    for attempt in range(tries):
        x = random.randrange(2, n-1)
        y = x
        c = random.randrange(1, n-1)
        d = 1
        f = lambda x: (pow(x,2,n) + c) % n
        for i in range(max_iter):
            x = f(x)
            y = f(f(y))
            d = math.gcd(abs(x-y), n)
            if d == n:
                break
            if d > 1:
                return d
    return None

# ---------- Pollard p-1 ----------
def pollard_p_minus_1(n, B=50000, a=2):
    # compute A = product of p^{floor(log_p B)} for primes p <= B
    from sympy import primerange
    A = 1
    for pr in primerange(2, B+1):
        e = int(math.log(B, pr))
        A *= pow(pr, e)
    x = pow(a, A, n)
    g = math.gcd(x-1, n)
    if 1 < g < n:
        return g
    return None

# ---------- wrappers for external tools ----------
def run_gmp_ecm(n, B1=100000, curves=200):
    """
    Requires gmp-ecm binary 'ecm' installed.
    Example: ecm -c <curves> -B1 <B1> <n>
    This function will shell out and try to capture a factor, returns factor or None.
    """
    if not shutil.which("ecm"):
        print("gmp-ecm (ecm) not found in PATH.")
        return None
    cmd = ["ecm", "-c", str(curves), "-B1", str(B1), str(n)]
    print("Running:", " ".join(cmd))
    try:
        out = subprocess.check_output(cmd, stderr=subprocess.STDOUT, universal_newlines=True, timeout=3600)
    except subprocess.CalledProcessError as e:
        out = e.output
    except subprocess.TimeoutExpired:
        print("gmp-ecm timed out")
        return None
    # try to parse any factor:
    for line in out.splitlines():
        line = line.strip()
        if line.isdigit():
            f = int(line)
            if 1 < f < n:
                return f
    # sometimes ecm prints "Factor: <f>"
    import re
    m = re.search(r"Factor:\s*(\d+)", out)
    if m:
        return int(m.group(1))
    return None

# ---------- attempt to use functions ----------
def try_factor(n):
    print("Starting light factoring attempts for N (bits={}):".format(n.bit_length()))
    # trial division
    p = trial_division(n, limit=20000)
    if p:
        print("Found small factor via trial division:", p)
        return p
    # Pollard Rho
    print("Trying Pollard Rho...")
    p = pollard_rho(n, tries=12, max_iter=200000)
    if p:
        print("Found factor via Pollard Rho:", p)
        return p
    # Pollard p-1
    for B in (5000, 20000, 50000):
        print("Trying Pollard p-1 with B =", B)
        p = pollard_p_minus_1(n, B=B, a=2)
        if p:
            print("Found factor via p-1:", p)
            return p
    # Try gmp-ecm if available
    if shutil.which("ecm"):
        print("gmp-ecm available — trying some curves...")
        f = run_gmp_ecm(n, B1=100000, curves=500)
        if f:
            print("Found factor via gmp-ecm:", f)
            return f
    print("No factor found by light attempts.")
    return None

# ---------- helper: compute modular roots given p,q -----------
def recover_x_from_cipher(c, e, p_val, q_val):
    # compute phi or lambda
    phi = (p_val-1)*(q_val-1)
    # compute e^-1 mod phi (or mod lcm?)
    # safer to use lambda = lcm(p-1, q-1)
    lamb = (p_val-1)//math.gcd(p_val-1, q_val-1) * (q_val-1)
    try:
        d = pow(e, -1, lamb)
    except TypeError:
        # Python <3.8 fallback
        from sympy import invert
        d = invert(e, lamb)
    x = pow(c, d, p_val*q_val)
    return x

# ---------- main flow ----------
if __name__ == "__main__":
    print("N bits:", N.bit_length())
    # quick checks whether c1 or c2 are < N and perfect powers (no modular reduction)
    ir = integer_root(c1, e1)
    if ir:
        print("Found integer e1-th root (no mod reduction): x =", ir)
    else:
        print("No plain integer e1-th root for c1.")

    ir2 = integer_root(c2, e2)
    if ir2:
        print("Found integer e2-th root (no mod reduction): y =", ir2)
    else:
        print("No plain integer e2-th root for c2.")

    # Try to factor N lightly
    f = try_factor(N)
    if f:
        co = N // f
        print("Found factor f:", f)
        print("cofactor:", co)
        print("Primality f:", is_probable_prime(f))
        print("Primality cofactor:", is_probable_prime(co))
        # If both primes, we can continue to recover x,y and then p,q via linear forms
        if is_probable_prime(f) and is_probable_prime(co):
            p_found = f; q_found = co
            # compute x and y by modular roots (if e values invertible mod lambda)
            lamb = (p_found-1)//math.gcd(p_found-1, q_found-1)*(q_found-1)
            try:
                d1 = pow(e1, -1, lamb)
                d2 = pow(e2, -1, lamb)
            except TypeError:
                from sympy import invert
                d1 = invert(e1, lamb)
                d2 = invert(e2, lamb)
            x = pow(c1, d1, N)
            y = pow(c2, d2, N)
            print("Recovered x,y (mod N):", x, y)
            # compute p,q from x,y (integer formula)
            p_calc = 3*y - 7*x
            q_calc = 5*x - 2*y
            print("Computed p,q from x,y via linear combs (may need signs):")
            print("p_calc:", p_calc)
            print("q_calc:", q_calc)
            print("Check p_calc*q_calc == N?", p_calc*q_calc == N)
            if p_calc*q_calc == N:
                print("Success! p,q found.")
            else:
                print("Mismatch: p_calc*q_calc != N (maybe x,y are only residues mod N).")
    else:
        print("No factor found by light methods. Try using GMP-ECM or yafu/msieve with more resources.")
        print("If you have gmp-ecm installed, consider running (example):")
        print("  ecm -c 1000 -B1 100000 {}".format(N))
        print("Or use yafu (which will call ECM and may run GNFS if needed):")
        print("  yafu 'factor({})'".format(N))

